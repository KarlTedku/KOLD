<?php

namespace App\Services;

use App\Models\KolAiTag;
use App\Models\KolProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class KolTaggingService
{
    private const CATEGORIES = ['content', 'collaboration', 'brand_fit', 'audience', 'region', 'tone', 'platform'];

    /**
     * @return Collection<int, KolAiTag>
     */
    public function generateSuggestedTags(KolProfile $profile): Collection
    {
        $profile->loadMissing(['user.socialAccounts', 'cardLinks']);

        $aiTags = $this->openAiTags($profile);
        $tags = $aiTags ?: $this->fallbackTags($profile);
        $source = $aiTags ? 'ai' : 'fallback';
        $approved = $profile->aiTags()
            ->where('status', 'approved')
            ->get(['category', 'label'])
            ->map(fn (KolAiTag $tag) => $tag->category.':'.$tag->label);

        $profile->aiTags()->where('status', 'suggested')->delete();

        return collect($tags)
            ->filter(fn ($tag) => is_array($tag))
            ->map(fn (array $tag) => $this->normalizeTag($tag, $source))
            ->filter()
            ->reject(fn (array $tag) => $approved->contains($tag['category'].':'.$tag['label']))
            ->unique(fn (array $tag) => $tag['category'].':'.$tag['label'])
            ->map(function (array $tag) use ($profile): KolAiTag {
                return $profile->aiTags()->create([
                    'category' => $tag['category'],
                    'label' => $tag['label'],
                    'status' => 'suggested',
                    'confidence' => $tag['confidence'],
                    'rationale' => $tag['rationale'],
                    'source' => $tag['source'],
                ]);
            })
            ->values();
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    protected function openAiTags(KolProfile $profile): ?array
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
                            'content' => 'You classify KOL profiles for broad, non-sensitive brand matching. Return JSON {"tags":[{"category":"content|collaboration|brand_fit|audience|region|tone|platform","label":"...","confidence":1-100,"rationale":"short Traditional Chinese reason"}]}. Avoid ethnicity, religion, health, politics, sexuality, income, or other sensitive demographic guesses. Broad labels such as young women, families, and Hong Kong consumers are allowed.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $this->profileSummary($profile),
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
        $tags = $decoded['tags'] ?? null;

        return is_array($tags) ? $tags : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function fallbackTags(KolProfile $profile): array
    {
        $summary = Str::lower($this->profileSummary($profile));
        $tags = [];

        foreach ((array) $profile->niches as $niche) {
            $tags[] = $this->tag('content', $niche, 78, '來自 KOL 自填 niche。');
        }

        foreach ((array) $profile->regions as $region) {
            $tags[] = $this->tag('region', $region, 76, '來自 KOL 自填地區。');
        }

        foreach ($profile->user?->socialAccounts ?? [] as $account) {
            $tags[] = $this->tag('platform', ucfirst((string) $account->platform), 72, '來自已連結或手動填寫的社群平台。');
        }

        $keywordMap = [
            '美妝' => ['content', '美妝護膚', '適合護膚、彩妝、個人護理品牌。'],
            '護膚' => ['brand_fit', '護膚品牌', '內容提及護膚或 skincare。'],
            '親子' => ['audience', '親子家庭', '內容提及親子或家庭場景。'],
            '健身' => ['content', '健身健康', '內容提及健身、運動或 wellness。'],
            '旅遊' => ['content', '旅遊生活', '內容提及旅遊或戶外體驗。'],
            '美食' => ['content', '餐飲美食', '內容提及食評、餐飲或 cafe。'],
            '科技' => ['brand_fit', '科技產品', '內容提及 3C、科技或 gadget。'],
            '時尚' => ['content', '時尚穿搭', '內容提及時尚、穿搭或造型。'],
            '香港' => ['audience', '香港本地生活消費', '內容或地區指向香港本地受眾。'],
            '短影音' => ['collaboration', '短影音', 'Profile 提及短影音內容。'],
            'reels' => ['collaboration', '短影音', 'Profile 提及 Reels。'],
            '開箱' => ['collaboration', '產品試用', '內容適合產品試用或開箱。'],
            '測評' => ['collaboration', '產品試用', '內容包含產品測評。'],
            '真實' => ['tone', '真實分享', 'Profile 強調真實體驗。'],
        ];

        foreach ($keywordMap as $keyword => [$category, $label, $reason]) {
            if (Str::contains($summary, Str::lower($keyword))) {
                $tags[] = $this->tag($category, $label, 68, $reason);
            }
        }

        if ($tags === []) {
            $tags[] = $this->tag('content', '生活方式', 55, '根據 profile 內容不足時的保守分類。');
            $tags[] = $this->tag('collaboration', '品牌合作', 55, '適合作為通用合作候選。');
        }

        return $tags;
    }

    protected function profileSummary(KolProfile $profile): string
    {
        $lines = [
            'Name: '.$profile->display_name,
            'Bio: '.($profile->bio ?? ''),
            'Niches: '.implode(',', (array) $profile->niches),
            'Regions: '.implode(',', (array) $profile->regions),
            'Languages: '.implode(',', (array) $profile->languages),
            'Headline: '.($profile->card_headline ?? ''),
            'Photo URLs: '.implode(',', (array) $profile->photos),
        ];

        foreach ($profile->cardLinks as $link) {
            $lines[] = 'Link: '.$link->title.' '.$link->url;
        }

        foreach ($profile->user?->socialAccounts ?? [] as $account) {
            $lines[] = sprintf('Social: %s @%s followers %s', $account->platform, $account->handle, $account->follower_count);
        }

        return implode("\n", $lines);
    }

    /**
     * @return array<string, mixed>
     */
    protected function tag(string $category, string $label, int $confidence, string $rationale): array
    {
        return compact('category', 'label', 'confidence', 'rationale');
    }

    /** @param array<string, mixed> $tag */
    protected function normalizeTag(array $tag, string $source): ?array
    {
        $category = (string) ($tag['category'] ?? '');
        $label = trim((string) ($tag['label'] ?? ''));

        if (! in_array($category, self::CATEGORIES, true) || $label === '' || $this->isSensitiveLabel($label)) {
            return null;
        }

        return [
            'category' => $category,
            'label' => Str::limit($label, 80, ''),
            'confidence' => min(100, max(1, (int) ($tag['confidence'] ?? 65))),
            'rationale' => filled($tag['rationale'] ?? null)
                ? Str::limit((string) $tag['rationale'], 300)
                : null,
            'source' => $source,
        ];
    }

    protected function isSensitiveLabel(string $label): bool
    {
        return Str::contains(Str::lower($label), [
            '宗教', 'religion', '種族', 'ethnicity', '政治', 'politic', '性取向',
            'sexual orientation', '疾病', 'disability', '殘疾', '收入', 'income',
        ]);
    }
}
