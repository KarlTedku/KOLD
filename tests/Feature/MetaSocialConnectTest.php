<?php

namespace Tests\Feature;

use App\Models\KolProfile;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\SocialSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class MetaSocialConnectTest extends TestCase
{
    use RefreshDatabase;

    public function test_meta_connect_redirect_uses_the_dedicated_connect_app(): void
    {
        config([
            'services.facebook.client_id' => 'login-app-id',
            'services.facebook.client_secret' => 'login-app-secret',
            'services.facebook_connect' => [
                'client_id' => 'connect-app-id',
                'client_secret' => 'connect-app-secret',
                'redirect' => 'https://kold.test/auth/facebook/callback',
            ],
        ]);

        $user = User::factory()->create(['role' => 'kol']);
        $provider = Mockery::mock();
        $provider->shouldReceive('setScopes')
            ->once()
            ->with(['pages_show_list', 'instagram_basic'])
            ->andReturnSelf();
        $provider->shouldReceive('redirect')->once()->andReturn(redirect('https://facebook.test/oauth'));
        Socialite::shouldReceive('driver')->once()->with('facebook')->andReturn($provider);

        $response = $this->actingAs($user)->get(route('social.connect', 'facebook'));

        $response->assertRedirect('https://facebook.test/oauth');
        $response->assertSessionHas('social_connect.provider', 'facebook');
        $this->assertSame('connect-app-id', config('services.facebook.client_id'));
    }

    public function test_meta_connect_callback_stores_candidates_without_creating_a_user(): void
    {
        config(['services.facebook_connect' => [
            'client_id' => 'connect-app-id',
            'client_secret' => 'connect-app-secret',
            'redirect' => 'https://kold.test/auth/facebook/callback',
        ]]);

        $user = User::factory()->create(['role' => 'kol']);
        $beforeCount = User::count();

        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn(new class
        {
            public string $token = 'meta-user-token';
        });
        Socialite::shouldReceive('driver')->once()->with('facebook')->andReturn($provider);

        $sync = Mockery::mock(SocialSyncService::class);
        $sync->shouldReceive('facebookPageInstagramCandidates')
            ->once()
            ->with('meta-user-token')
            ->andReturn([$this->candidate('page-1', 'ig-1', 'kold.creator')]);
        $this->app->instance(SocialSyncService::class, $sync);

        $response = $this
            ->actingAs($user)
            ->withSession(['social_connect' => ['provider' => 'facebook']])
            ->get(route('auth.callback', 'facebook'));

        $response->assertRedirect(route('social.meta.select'));
        $response->assertSessionHas('meta_connect_candidates');
        $this->assertSame($beforeCount, User::count());
        $this->assertSame('connect-app-id', config('services.facebook.client_id'));
    }

    public function test_user_can_store_multiple_meta_instagram_accounts(): void
    {
        $user = User::factory()->create(['role' => 'kol']);
        $first = $this->candidate('page-1', 'ig-1', 'first.creator', 12000);
        $second = $this->candidate('page-2', 'ig-2', 'second.creator', 34000);

        $response = $this
            ->actingAs($user)
            ->withSession([
                'social_connect' => ['provider' => 'facebook'],
                'meta_connect_candidates' => [$first, $second],
            ])
            ->post(route('social.meta.store'), [
                'accounts' => [$first['key'], $second['key']],
                'primary' => $second['key'],
            ]);

        $response->assertRedirect(route('social.index'));
        $response->assertSessionHas('status', 'Meta／Instagram 帳號已連結。');

        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'platform' => 'instagram',
            'account_type' => 'instagram_business',
            'external_id' => 'ig-1',
            'handle' => 'first.creator',
            'follower_count' => 12000,
            'is_primary' => false,
        ]);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'platform' => 'instagram',
            'account_type' => 'instagram_business',
            'external_id' => 'ig-2',
            'handle' => 'second.creator',
            'follower_count' => 34000,
            'is_primary' => true,
        ]);
        $this->assertSame(2, SocialAccount::query()->where('platform', 'facebook')->count());
        $this->assertSame(2, SocialAccount::query()->where('platform', 'instagram')->count());
        $user->refresh();
        $this->assertSame('https://example.com/second.creator.jpg', $user->avatar);
        $this->assertSame(User::AVATAR_SOURCE_META, $user->avatar_source);
    }

    public function test_connecting_meta_does_not_overwrite_a_manually_uploaded_avatar(): void
    {
        $user = User::factory()->create([
            'role' => 'kol',
            'avatar' => 'https://kold.test/storage/avatars/manual.jpg',
            'avatar_source' => User::AVATAR_SOURCE_MANUAL,
        ]);
        $candidate = $this->candidate('page-1', 'ig-1', 'professional.creator');

        $this->actingAs($user)
            ->withSession([
                'social_connect' => ['provider' => 'facebook'],
                'meta_connect_candidates' => [$candidate],
            ])
            ->post(route('social.meta.store'), [
                'accounts' => [$candidate['key']],
                'primary' => $candidate['key'],
            ])
            ->assertRedirect(route('social.index'));

        $user->refresh();
        $this->assertSame('https://kold.test/storage/avatars/manual.jpg', $user->avatar);
        $this->assertSame(User::AVATAR_SOURCE_MANUAL, $user->avatar_source);
    }

    public function test_regular_oauth_login_does_not_overwrite_manual_or_professional_avatar(): void
    {
        foreach ([User::AVATAR_SOURCE_MANUAL, User::AVATAR_SOURCE_META] as $source) {
            $user = User::factory()->create([
                'role' => 'kol',
                'email' => $source.'@example.com',
                'provider' => 'facebook',
                'provider_id' => 'facebook-'.$source,
                'avatar' => 'https://example.com/'.$source.'.jpg',
                'avatar_source' => $source,
            ]);

            $socialUser = new class($user)
            {
                public function __construct(private readonly User $user) {}

                public function getId(): string
                {
                    return (string) $this->user->provider_id;
                }

                public function getEmail(): string
                {
                    return (string) $this->user->email;
                }

                public function getName(): string
                {
                    return 'OAuth Creator';
                }

                public function getNickname(): ?string
                {
                    return null;
                }

                public function getAvatar(): string
                {
                    return 'https://example.com/new-oauth-avatar.jpg';
                }
            };
            $provider = Mockery::mock();
            $provider->shouldReceive('user')->once()->andReturn($socialUser);
            Socialite::shouldReceive('driver')->once()->with('facebook')->andReturn($provider);

            $this->get(route('auth.callback', 'facebook'))->assertRedirect(route('dashboard'));

            $user->refresh();
            $this->assertSame('https://example.com/'.$source.'.jpg', $user->avatar);
            $this->assertSame($source, $user->avatar_source);
            auth()->logout();
        }
    }

    public function test_meta_connect_callback_reports_when_no_instagram_account_is_found(): void
    {
        $user = User::factory()->create(['role' => 'kol']);

        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn(new class
        {
            public string $token = 'meta-user-token';
        });
        Socialite::shouldReceive('driver')->once()->with('facebook')->andReturn($provider);

        $sync = Mockery::mock(SocialSyncService::class);
        $sync->shouldReceive('facebookPageInstagramCandidates')
            ->once()
            ->with('meta-user-token')
            ->andReturn([[
                'key' => sha1('page-1:'),
                'page_id' => 'page-1',
                'page_name' => 'Page page-1',
                'page_access_token' => 'page-token-page-1',
                'page_tasks' => ['MANAGE'],
                'instagram' => null,
            ]]);
        $this->app->instance(SocialSyncService::class, $sync);

        $response = $this
            ->actingAs($user)
            ->withSession(['social_connect' => ['provider' => 'facebook']])
            ->get(route('auth.callback', 'facebook'));

        $response->assertRedirect(route('social.index'));
        $response->assertSessionHas('error', '此 Facebook 帳號暫時未找到已連結的 Instagram Business／Creator 帳號。請確認 IG 已連到 Facebook Page。');
    }

    public function test_discover_follower_filter_uses_total_social_followers(): void
    {
        $brand = User::factory()->create(['role' => 'brand']);
        $kol = User::factory()->create(['role' => 'kol', 'name' => 'Total Followers KOL']);
        KolProfile::query()->create([
            'user_id' => $kol->id,
            'display_name' => 'Total Followers KOL',
            'bio' => 'Published creator',
            'status' => 'published',
        ]);

        SocialAccount::query()->create([
            'user_id' => $kol->id,
            'platform' => 'instagram',
            'account_type' => 'instagram_business',
            'external_id' => 'ig-1',
            'handle' => 'one',
            'follower_count' => 10000,
            'metrics_json' => [],
        ]);
        SocialAccount::query()->create([
            'user_id' => $kol->id,
            'platform' => 'instagram',
            'account_type' => 'instagram_business',
            'external_id' => 'ig-2',
            'handle' => 'two',
            'follower_count' => 20000,
            'metrics_json' => [],
        ]);

        $this->actingAs($brand)
            ->get(route('discover.index', ['followers' => '10_50k']))
            ->assertOk()
            ->assertSee('Total Followers KOL');

        $this->actingAs($brand)
            ->get(route('discover.index', ['followers' => '50_200k']))
            ->assertOk()
            ->assertDontSee('Total Followers KOL');
    }

    /**
     * @return array<string, mixed>
     */
    protected function candidate(string $pageId, string $instagramId, string $username, int $followers = 1000): array
    {
        return [
            'key' => sha1($pageId.':'.$instagramId),
            'page_id' => $pageId,
            'page_name' => 'Page '.$pageId,
            'page_access_token' => 'page-token-'.$pageId,
            'page_tasks' => ['MANAGE'],
            'instagram' => [
                'id' => $instagramId,
                'username' => $username,
                'name' => $username,
                'profile_picture_url' => 'https://example.com/'.$username.'.jpg',
                'followers_count' => $followers,
                'media_count' => 5,
            ],
        ];
    }
}
