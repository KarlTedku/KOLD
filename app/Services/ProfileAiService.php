<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ProfileAiService
{
    /**
     * @return array{bio: string, niches: array<int, string>, tone: string, collaboration_types: array<int, string>}
     */
    public function generateDraft(User $user, ?string $extraNotes = null): array
    {
        $summary = $this->buildSummary($user, $extraNotes);
        $apiKey = config('services.openai.api_key');

        if (! $apiKey) {
            return $this->fallbackDraft($user, $summary);
        }

        $response = Http::withToken($apiKey)
            ->timeout(30)
            ->post(rtrim(config('services.openai.base_url'), '/').'/chat/completions', [
                'model' => config('services.openai.model', 'gpt-4o-mini'),
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You write Traditional Chinese profile drafts for a KOL-brand matching platform. Return JSON with keys: bio (string), niches (string array), tone (string), collaboration_types (string array). Keep bio under 120 Chinese characters.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $summary,
                    ],
                ],
            ]);

        if (! $response->successful()) {
            return $this->fallbackDraft($user, $summary);
        }

        $content = data_get($response->json(), 'choices.0.message.content');
        $decoded = json_decode((string) $content, true);

        if (! is_array($decoded)) {
            return $this->fallbackDraft($user, $summary);
        }

        return [
            'bio' => (string) ($decoded['bio'] ?? ''),
            'niches' => array_values(array_filter((array) ($decoded['niches'] ?? []))),
            'tone' => (string) ($decoded['tone'] ?? '專業親和'),
            'collaboration_types' => array_values(array_filter((array) ($decoded['collaboration_types'] ?? []))),
        ];
    }

    protected function buildSummary(User $user, ?string $extraNotes): string
    {
        $lines = [
            '角色：'.($user->role === 'brand' ? '品牌' : 'KOL'),
            '名稱：'.$user->profileDisplayName(),
        ];

        foreach ($user->socialAccounts as $account) {
            $lines[] = sprintf(
                '平台 %s：@%s，粉絲 %s',
                $account->platform,
                $account->handle ?? '未知',
                number_format((int) $account->follower_count)
            );
        }

        if ($user->isKol() && $user->kolProfile) {
            $lines[] = '現有簡介：'.($user->kolProfile->bio ?? '');
            $lines[] = '現有 niches：'.implode(',', $user->kolProfile->niches ?? []);
        }

        if ($user->isBrand() && $user->brandProfile) {
            $lines[] = '現有簡介：'.($user->brandProfile->bio ?? '');
            $lines[] = '產業：'.implode(',', $user->brandProfile->industries ?? []);
        }

        if ($extraNotes) {
            $lines[] = '補充：'.$extraNotes;
        }

        return implode("\n", $lines);
    }

    /**
     * @return array{bio: string, niches: array<int, string>, tone: string, collaboration_types: array<int, string>}
     */
    protected function fallbackDraft(User $user, string $summary): array
    {
        $name = $user->profileDisplayName();
        $handles = $user->socialAccounts->pluck('handle')->filter()->implode('、');

        if ($user->isBrand()) {
            return [
                'bio' => "{$name} 正在尋找合適的創作者合作，重視真實口碑與長期夥伴關係。".($handles ? " 已連結：{$handles}。" : ''),
                'niches' => ['品牌合作', '內容行銷', '社群推廣'],
                'tone' => '專業清晰',
                'collaboration_types' => ['產品體驗', '短影音', '長期代言'],
            ];
        }

        $nicheSeed = Str::of($summary)->contains(['美妝', 'beauty']) ? ['美妝', '生活'] : ['生活', '生活方式', '內容創作'];

        return [
            'bio' => "{$name} 專注真實分享與品牌故事，適合需要親和力與內容產出的合作。".($handles ? " 活躍於 {$handles}。" : ''),
            'niches' => $nicheSeed,
            'tone' => '真誠親和',
            'collaboration_types' => ['圖文種草', '短影音', '活動出席'],
        ];
    }
}
