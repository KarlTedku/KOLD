<?php

namespace App\Services;

use App\Models\KolAiTag;
use App\Models\KolProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class BrandBriefMatchService
{
    private const CATEGORIES = ['content', 'brand_fit', 'collaboration', 'audience', 'region', 'platform', 'tone'];

    private const WEIGHTS = [
        'content' => 5,
        'brand_fit' => 5,
        'collaboration' => 4,
        'platform' => 3,
        'audience' => 2,
        'tone' => 2,
        'region' => 1,
    ];

    private const PRIMARY_CATEGORIES = ['content', 'brand_fit', 'collaboration'];

    /**
     * @param  array<string, mixed>|string  $brief
     * @return array{intent: array<string, mixed>, primary: Collection<int, array<string, mixed>>, related: Collection<int, array<string, mixed>>, results: Collection<int, array<string, mixed>>}
     */
    public function match(array|string $brief): array
    {
        $input = is_string($brief) ? ['notes' => $brief] : $brief;
        $intent = $this->openAiIntent($input) ?: $this->fallbackIntent($input);
        $intent = $this->normalizeIntent($intent, $input);

        $profiles = KolProfile::query()
            ->with(['user.socialAccounts', 'approvedAiTags'])
            ->where('status', 'published')
            ->whereHas('approvedAiTags')
            ->get();

        $results = $profiles
            ->map(fn (KolProfile $profile): array => $this->scoreProfile($profile, $intent))
            ->filter(fn (array $result): bool => $result['matched_tags'] !== [] || $result['budget_match'])
            ->sortBy([
                ['score', 'desc'],
                ['followers', 'desc'],
            ])
            ->values();

        return [
            'intent' => $intent,
            'primary' => $results->where('tier', 'primary')->values(),
            'related' => $results->where('tier', 'related')->values(),
            'results' => $results,
        ];
    }

    /** @param array<string, mixed> $input */
    protected function openAiIntent(array $input): ?array
    {
        $apiKey = config('services.openai.api_key');
        if (! $apiKey) {
            return null;
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(20)
                ->post(rtrim(config('services.openai.base_url'), '/').'/chat/completions', [
                    'model' => config('services.openai.model', 'gpt-4o-mini'),
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Convert a brand campaign brief into broad, non-sensitive KOL matching criteria. Return JSON {"summary":"Traditional Chinese summary","criteria":{"content":[],"brand_fit":[],"collaboration":[],"audience":[],"region":[],"platform":[],"tone":[]}}. Keep labels concise and use Traditional Chinese except platform names.',
                        ],
                        [
                            'role' => 'user',
                            'content' => json_encode([
                                'brief' => $input,
                                'available_approved_tags' => $this->availableTagTaxonomy(),
                                'instruction' => 'Use labels from available_approved_tags whenever possible so results can match exactly.',
                            ], JSON_UNESCAPED_UNICODE),
                        ],
                    ],
                ]);
        } catch (Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $decoded = json_decode((string) data_get($response->json(), 'choices.0.message.content'), true);
        if (! is_array($decoded) || ! is_array($decoded['criteria'] ?? null)) {
            return null;
        }

        $decoded['source'] = 'ai';

        return $decoded;
    }

    /** @param array<string, mixed> $input */
    protected function fallbackIntent(array $input): array
    {
        $text = Str::lower(implode(' ', array_filter([
            $input['product'] ?? null,
            $input['audience'] ?? null,
            $input['region'] ?? null,
            $input['platform'] ?? null,
            $input['collaboration'] ?? null,
            $input['notes'] ?? null,
            $input['brief'] ?? null,
        ])));

        $criteria = array_fill_keys(self::CATEGORIES, []);
        $keywordMap = [
            '美妝' => ['content' => ['美妝護膚'], 'brand_fit' => ['護膚品牌']],
            '護膚' => ['content' => ['美妝護膚'], 'brand_fit' => ['護膚品牌']],
            'skincare' => ['content' => ['美妝護膚'], 'brand_fit' => ['護膚品牌']],
            'beauty' => ['content' => ['美妝護膚']],
            '親子' => ['audience' => ['親子家庭']],
            '媽媽' => ['audience' => ['親子家庭']],
            'family' => ['audience' => ['親子家庭']],
            '健身' => ['content' => ['健身健康']],
            '運動' => ['content' => ['健身健康']],
            'fitness' => ['content' => ['健身健康']],
            '旅遊' => ['content' => ['旅遊生活']],
            'travel' => ['content' => ['旅遊生活']],
            '美食' => ['content' => ['餐飲美食']],
            '餐廳' => ['content' => ['餐飲美食']],
            'food' => ['content' => ['餐飲美食']],
            '科技' => ['brand_fit' => ['科技產品']],
            '3c' => ['brand_fit' => ['科技產品']],
            'tech' => ['brand_fit' => ['科技產品']],
            '時尚' => ['content' => ['時尚穿搭']],
            '穿搭' => ['content' => ['時尚穿搭']],
            'fashion' => ['content' => ['時尚穿搭']],
            '香港' => ['region' => ['香港'], 'audience' => ['香港本地生活消費']],
            'hong kong' => ['region' => ['香港'], 'audience' => ['香港本地生活消費']],
            '年輕女性' => ['audience' => ['年輕女性']],
            'young women' => ['audience' => ['年輕女性']],
            'reels' => ['platform' => ['Instagram'], 'collaboration' => ['短影音']],
            'instagram' => ['platform' => ['Instagram']],
            ' ig ' => ['platform' => ['Instagram']],
            'youtube' => ['platform' => ['Youtube']],
            'tiktok' => ['platform' => ['TikTok']],
            '短影音' => ['collaboration' => ['短影音']],
            '試用' => ['collaboration' => ['產品試用']],
            '開箱' => ['collaboration' => ['產品試用']],
            '活動' => ['collaboration' => ['活動出席']],
            '直播' => ['collaboration' => ['直播']],
        ];

        foreach ($keywordMap as $keyword => $mapped) {
            if (! Str::contains(' '.$text.' ', $keyword)) {
                continue;
            }

            foreach ($mapped as $category => $labels) {
                $criteria[$category] = array_merge($criteria[$category], $labels);
            }
        }

        if (collect($criteria)->flatten()->isEmpty()) {
            $criteria['content'] = ['生活方式'];
            $criteria['collaboration'] = ['品牌合作'];
        }

        return [
            'summary' => '已根據產品、受眾及合作要求整理候選條件。',
            'criteria' => $criteria,
            'source' => 'fallback',
        ];
    }

    /**
     * @param  array<string, mixed>  $intent
     * @param  array<string, mixed>  $input
     */
    protected function normalizeIntent(array $intent, array $input): array
    {
        $criteria = [];
        foreach (self::CATEGORIES as $category) {
            $criteria[$category] = collect(data_get($intent, "criteria.{$category}", []))
                ->filter(fn ($label) => is_string($label))
                ->map(fn (string $label) => trim(Str::limit($label, 60, '')))
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        return [
            'summary' => is_string($intent['summary'] ?? null)
                ? Str::limit($intent['summary'], 240)
                : '已整理品牌合作需要。',
            'criteria' => $criteria,
            'tags' => collect($criteria)->flatten()->unique()->values()->all(),
            'source' => ($intent['source'] ?? null) === 'ai' ? 'ai' : 'fallback',
            'budget_min' => $this->nullableInt($input['budget_min'] ?? null),
            'budget_max' => $this->nullableInt($input['budget_max'] ?? null),
        ];
    }

    /** @param array<string, mixed> $intent */
    protected function scoreProfile(KolProfile $profile, array $intent): array
    {
        $matched = [];
        $reasons = [];
        $score = 0;
        $hasPrimaryMatch = false;

        foreach ($profile->approvedAiTags as $tag) {
            $category = in_array($tag->category, self::CATEGORIES, true) ? $tag->category : 'content';
            if (! in_array($tag->label, $intent['criteria'][$category] ?? [], true)) {
                continue;
            }

            $matched[] = $tag->label;
            $score += self::WEIGHTS[$category] ?? 1;
            $hasPrimaryMatch = $hasPrimaryMatch || in_array($category, self::PRIMARY_CATEGORIES, true);
            $reasons[] = $this->reasonFor($category, $tag->label);
        }

        $budgetMatch = $this->budgetMatches($profile, $intent['budget_min'], $intent['budget_max']);
        if ($budgetMatch) {
            $score += 2;
            $reasons[] = '參考報價與預算有重疊';
        }

        return [
            'profile' => $profile,
            'matched_tags' => array_values(array_unique($matched)),
            'reasons' => array_values(array_unique($reasons)),
            'score' => $score,
            'tier' => $hasPrimaryMatch ? 'primary' : 'related',
            'budget_match' => $budgetMatch,
            'followers' => $profile->totalFollowers(),
        ];
    }

    protected function reasonFor(string $category, string $label): string
    {
        return match ($category) {
            'content' => "內容類別符合「{$label}」",
            'brand_fit' => "品牌方向符合「{$label}」",
            'collaboration' => "合作形式符合「{$label}」",
            'platform' => "平台強項符合「{$label}」",
            'audience' => "受眾方向符合「{$label}」",
            'region' => "地區符合「{$label}」",
            'tone' => "內容風格符合「{$label}」",
            default => "符合「{$label}」",
        };
    }

    protected function budgetMatches(KolProfile $profile, ?int $budgetMin, ?int $budgetMax): bool
    {
        if (($budgetMin === null && $budgetMax === null) || ($profile->rate_min === null && $profile->rate_max === null)) {
            return false;
        }

        $requestedMin = $budgetMin ?? 0;
        $requestedMax = $budgetMax ?? PHP_INT_MAX;
        $profileMin = $profile->rate_min ?? 0;
        $profileMax = $profile->rate_max ?? PHP_INT_MAX;

        return $requestedMin <= $profileMax && $requestedMax >= $profileMin;
    }

    protected function nullableInt(mixed $value): ?int
    {
        return is_numeric($value) ? max(0, (int) $value) : null;
    }

    /** @return array<string, array<int, string>> */
    protected function availableTagTaxonomy(): array
    {
        return KolAiTag::query()
            ->where('status', 'approved')
            ->whereIn('category', self::CATEGORIES)
            ->get(['category', 'label'])
            ->groupBy('category')
            ->map(fn (Collection $tags) => $tags->pluck('label')->unique()->values()->all())
            ->all();
    }
}
