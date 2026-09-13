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
        $handle = ltrim((string) ($payload['handle'] ?? ''), '@');
        $accountType = $payload['account_type'] ?? 'manual';
        $lookup = [
            'user_id' => $user->id,
            'platform' => $platform,
            'account_type' => $accountType,
        ];

        if (filled($payload['external_id'] ?? null)) {
            $lookup['external_id'] = $payload['external_id'];
        } else {
            $lookup['handle'] = $handle;
        }

        return SocialAccount::query()->updateOrCreate(
            $lookup,
            [
                'account_type' => $accountType,
                'external_id' => $payload['external_id'] ?? null,
                'page_id' => $payload['page_id'] ?? null,
                'page_name' => $payload['page_name'] ?? null,
                'handle' => $handle,
                'follower_count' => (int) ($payload['follower_count'] ?? 0),
                'metrics_json' => $payload['metrics_json'] ?? [],
                'is_primary' => (bool) ($payload['is_primary'] ?? false),
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

    /**
     * @return array<int, array<string, mixed>>
     */
    public function facebookPageInstagramCandidates(string $accessToken): array
    {
        $response = Http::get('https://graph.facebook.com/v19.0/me/accounts', [
            'fields' => 'id,name,access_token,tasks,instagram_business_account{id,username,name,profile_picture_url,followers_count,media_count}',
            'access_token' => $accessToken,
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('無法讀取 Facebook Page／Instagram Business 資料。');
        }

        return collect((array) data_get($response->json(), 'data', []))
            ->map(function (array $page): array {
                $instagram = data_get($page, 'instagram_business_account');

                return [
                    'key' => sha1((string) data_get($page, 'id').':'.(string) data_get($instagram, 'id')),
                    'page_id' => (string) data_get($page, 'id'),
                    'page_name' => (string) data_get($page, 'name'),
                    'page_access_token' => (string) data_get($page, 'access_token'),
                    'page_tasks' => (array) data_get($page, 'tasks', []),
                    'instagram' => $instagram ? [
                        'id' => (string) data_get($instagram, 'id'),
                        'username' => (string) data_get($instagram, 'username'),
                        'name' => (string) data_get($instagram, 'name'),
                        'profile_picture_url' => (string) data_get($instagram, 'profile_picture_url'),
                        'followers_count' => (int) data_get($instagram, 'followers_count', 0),
                        'media_count' => (int) data_get($instagram, 'media_count', 0),
                    ] : null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $candidate
     * @return array{page: SocialAccount, instagram: SocialAccount|null}
     */
    public function syncFacebookPageCandidate(User $user, array $candidate, bool $isPrimary = false): array
    {
        $page = $this->upsertManual($user, 'facebook', [
            'account_type' => 'facebook_page',
            'external_id' => $candidate['page_id'] ?? null,
            'page_id' => $candidate['page_id'] ?? null,
            'page_name' => $candidate['page_name'] ?? null,
            'handle' => $candidate['page_name'] ?? 'facebook-page',
            'follower_count' => 0,
            'access_token' => $candidate['page_access_token'] ?? null,
            'is_primary' => false,
            'metrics_json' => [
                'source' => 'meta_oauth',
                'tasks' => $candidate['page_tasks'] ?? [],
            ],
        ]);

        $instagram = $candidate['instagram'] ?? null;
        if (! is_array($instagram) || blank($instagram['id'] ?? null)) {
            return ['page' => $page, 'instagram' => null];
        }

        $account = $this->upsertManual($user, 'instagram', [
            'account_type' => 'instagram_business',
            'external_id' => $instagram['id'],
            'page_id' => $candidate['page_id'] ?? null,
            'page_name' => $candidate['page_name'] ?? null,
            'handle' => $instagram['username'] ?: ($instagram['name'] ?? 'instagram'),
            'follower_count' => (int) ($instagram['followers_count'] ?? 0),
            'access_token' => $candidate['page_access_token'] ?? null,
            'is_primary' => $isPrimary,
            'metrics_json' => [
                'source' => 'meta_oauth',
                'name' => $instagram['name'] ?? null,
                'profile_picture_url' => $instagram['profile_picture_url'] ?? null,
                'media_count' => $instagram['media_count'] ?? null,
            ],
        ]);

        return ['page' => $page, 'instagram' => $account];
    }
}
