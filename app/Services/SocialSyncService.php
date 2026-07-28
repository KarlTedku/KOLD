<?php

namespace App\Services;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SocialSyncService
{
    /**
     * Sync a connected account, or create/update a manual placeholder when OAuth tokens are absent.
     *
     * @param  array{handle?: string, follower_count?: int, external_id?: string, access_token?: string, metrics_json?: array}  $payload
     */
    public function upsertManual(User $user, string $platform, array $payload): SocialAccount
    {
        return SocialAccount::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'platform' => $platform,
            ],
            [
                'external_id' => $payload['external_id'] ?? null,
                'handle' => ltrim((string) ($payload['handle'] ?? ''), '@'),
                'follower_count' => (int) ($payload['follower_count'] ?? 0),
                'metrics_json' => $payload['metrics_json'] ?? [],
                'access_token' => $payload['access_token'] ?? null,
                'synced_at' => now(),
            ]
        );
    }

    public function syncYouTube(User $user, string $accessToken): SocialAccount
    {
        $response = Http::withToken($accessToken)
            ->get('https://www.googleapis.com/youtube/v3/channels', [
                'part' => 'snippet,statistics',
                'mine' => 'true',
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('無法同步 YouTube 頻道資料。');
        }

        $item = data_get($response->json(), 'items.0');
        if (! $item) {
            throw new \RuntimeException('此 Google 帳號沒有 YouTube 頻道。');
        }

        return $this->upsertManual($user, 'youtube', [
            'external_id' => data_get($item, 'id'),
            'handle' => data_get($item, 'snippet.customUrl') ?: data_get($item, 'snippet.title'),
            'follower_count' => (int) data_get($item, 'statistics.subscriberCount', 0),
            'access_token' => $accessToken,
            'metrics_json' => [
                'title' => data_get($item, 'snippet.title'),
                'view_count' => data_get($item, 'statistics.viewCount'),
                'video_count' => data_get($item, 'statistics.videoCount'),
            ],
        ]);
    }

    public function syncInstagram(User $user, string $accessToken): SocialAccount
    {
        // Requires Instagram Graph / Business permissions; MVP stores what we can.
        $me = Http::get('https://graph.facebook.com/v19.0/me', [
            'fields' => 'id,name',
            'access_token' => $accessToken,
        ]);

        if (! $me->successful()) {
            throw new \RuntimeException('無法同步 Instagram／Meta 帳號資料。');
        }

        return $this->upsertManual($user, 'instagram', [
            'external_id' => data_get($me->json(), 'id'),
            'handle' => Str::slug((string) data_get($me->json(), 'name', 'instagram')),
            'follower_count' => 0,
            'access_token' => $accessToken,
            'metrics_json' => $me->json(),
        ]);
    }
}
