<?php

namespace Tests\Feature;

use App\Models\KolAiTag;
use App\Models\KolProfile;
use App\Models\KolProfileSlugAlias;
use App\Models\KolProfileSlugChange;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KolCardAiMatchingTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_kol_card_is_public_by_slug(): void
    {
        $kol = User::factory()->create(['role' => 'kol', 'avatar' => 'https://example.com/avatar.jpg']);
        $profile = KolProfile::query()->create([
            'user_id' => $kol->id,
            'display_name' => 'Mina Daily',
            'slug' => 'mina-daily',
            'bio' => '香港美妝生活創作者',
            'card_headline' => '美妝與生活好物分享',
            'external_contact_url' => 'https://example.com/contact',
            'niches' => ['美妝'],
            'regions' => ['香港'],
            'status' => 'published',
        ]);
        $profile->cardLinks()->create([
            'title' => 'Instagram',
            'url' => 'https://instagram.com/mina',
            'sort_order' => 10,
            'is_active' => true,
        ]);
        $profile->cardLinks()->create([
            'title' => 'Hidden Link',
            'url' => 'https://example.com/hidden',
            'sort_order' => 20,
            'is_active' => false,
        ]);

        $this->get(route('kol-card.show', 'mina-daily'))
            ->assertOk()
            ->assertSee('Mina Daily')
            ->assertSee('Instagram')
            ->assertSee('合作聯絡')
            ->assertDontSee('Hidden Link');
    }

    public function test_public_kol_card_is_standalone_and_hides_internal_rate(): void
    {
        $kol = User::factory()->create(['role' => 'kol']);
        KolProfile::query()->create([
            'user_id' => $kol->id,
            'display_name' => 'Standalone Creator',
            'slug' => 'standalone-creator',
            'bio' => '分享香港生活內容。',
            'rate_min' => 3000,
            'rate_max' => 8000,
            'status' => 'published',
        ]);

        $this->get(route('kol-card.show', 'standalone-creator'))
            ->assertOk()
            ->assertSee('kol-card-standalone', false)
            ->assertSee('Powered by KOLD')
            ->assertDontSee('參考合作價')
            ->assertDontSee('HK$')
            ->assertDontSee('合作項目')
            ->assertDontSee('我的檔案')
            ->assertDontSee('<header', false)
            ->assertDontSee('<footer', false);
    }

    public function test_card_owner_gets_a_discreet_edit_link_outside_the_shared_card(): void
    {
        $kol = User::factory()->create(['role' => 'kol']);
        KolProfile::query()->create([
            'user_id' => $kol->id,
            'display_name' => 'Card Owner',
            'slug' => 'card-owner-public',
            'status' => 'published',
        ]);

        $this->actingAs($kol)
            ->get(route('kol-card.show', 'card-owner-public'))
            ->assertOk()
            ->assertSee('返回編輯')
            ->assertDontSee('編輯我的頁面');
    }

    public function test_draft_kol_card_is_not_public(): void
    {
        $kol = User::factory()->create(['role' => 'kol']);
        KolProfile::query()->create([
            'user_id' => $kol->id,
            'display_name' => 'Draft KOL',
            'slug' => 'draft-kol',
            'status' => 'draft',
        ]);

        $this->get(route('kol-card.show', 'draft-kol'))->assertNotFound();
    }

    public function test_kol_can_update_card_and_manage_links(): void
    {
        $kol = User::factory()->create(['role' => 'kol']);
        KolProfile::query()->create([
            'user_id' => $kol->id,
            'display_name' => 'Card Owner',
            'status' => 'published',
        ]);

        $this->actingAs($kol)
            ->put(route('kol-card.update'), [
                'slug' => 'card-owner',
                'card_headline' => 'Creator card',
                'external_contact_url' => 'https://example.com/contact',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('kol_profiles', [
            'user_id' => $kol->id,
            'slug' => 'card-owner',
            'card_headline' => 'Creator card',
        ]);

        $this->actingAs($kol)
            ->post(route('kol-card.links.store'), [
                'title' => 'Media Kit',
                'url' => 'https://example.com/media-kit',
                'sort_order' => 5,
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('kol_card_links', [
            'title' => 'Media Kit',
            'sort_order' => 5,
            'is_active' => true,
        ]);
    }

    public function test_kol_can_style_card_and_choose_link_icons_with_a_real_embedded_preview(): void
    {
        $kol = User::factory()->create(['role' => 'kol']);
        $profile = KolProfile::query()->create([
            'user_id' => $kol->id,
            'display_name' => 'Styled Creator',
            'slug' => 'styled-creator',
            'status' => 'published',
        ]);

        $this->actingAs($kol)
            ->put(route('kol-card.update'), [
                'slug' => 'styled-creator',
                'card_headline' => 'Food, travel and creative life',
                'card_theme' => 'spotlight',
                'card_accent' => 'berry',
            ])
            ->assertRedirect();

        $this->assertSame('spotlight', $profile->fresh()->card_theme);
        $this->assertSame('berry', $profile->fresh()->card_accent);

        $this->actingAs($kol)
            ->post(route('kol-card.links.store'), [
                'title' => 'Instagram',
                'url' => 'https://instagram.com/styled-creator',
                'icon' => 'instagram',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('kol_card_links', [
            'kol_profile_id' => $profile->id,
            'icon' => 'instagram',
        ]);

        $this->get(route('profile.edit', ['step' => 'card']))
            ->assertOk()
            ->assertSee('data-card-preview-frame', false)
            ->assertSee('data-card-design-form', false)
            ->assertSee('聚焦');

        $this->get(route('kol-card.preview', ['embedded' => 1]))
            ->assertOk()
            ->assertSee('kol-card-theme-spotlight', false)
            ->assertSee('kol-card-accent-berry', false)
            ->assertDontSee('私人預覽')
            ->assertDontSee('返回編輯');

        $this->get(route('kol-card.show', 'styled-creator'))
            ->assertOk()
            ->assertSee('bi bi-instagram', false)
            ->assertSee('aria-label="社交平台"', false)
            ->assertSee('Food, travel and creative life');
    }

    public function test_kol_can_upload_replace_and_remove_a_card_background(): void
    {
        Storage::fake('public');
        $kol = User::factory()->create(['role' => 'kol']);
        $profile = KolProfile::query()->create([
            'user_id' => $kol->id,
            'display_name' => 'Cover Creator',
            'slug' => 'cover-creator',
            'status' => 'published',
        ]);

        $this->actingAs($kol)
            ->put(route('kol-card.update'), [
                'slug' => 'cover-creator',
                'card_theme' => 'spotlight',
                'card_accent' => 'coral',
                'background' => UploadedFile::fake()->image('cover.jpg', 1200, 1800)->size(400),
            ])
            ->assertRedirect(route('profile.edit', ['step' => 'links']));

        $firstPath = $profile->fresh()->card_background_path;
        $this->assertNotNull($firstPath);
        $this->assertSame('spotlight', $profile->fresh()->card_theme);
        $this->assertSame('coral', $profile->fresh()->card_accent);
        Storage::disk('public')->assertExists($firstPath);
        $this->get(route('kol-card.show', 'cover-creator'))
            ->assertOk()
            ->assertSee('kol-card-cover', false)
            ->assertSee(Storage::disk('public')->url($firstPath));

        $this->actingAs($kol)
            ->put(route('kol-card.update'), [
                'slug' => 'cover-creator',
                'background' => UploadedFile::fake()->image('new-cover.png', 1600, 1600)->size(500),
            ])
            ->assertRedirect();

        $newPath = $profile->fresh()->card_background_path;
        $this->assertNotSame($firstPath, $newPath);
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($newPath);

        $this->actingAs($kol)->delete(route('kol-card.background.destroy'))->assertRedirect();
        $this->assertNull($profile->fresh()->card_background_path);
        Storage::disk('public')->assertMissing($newPath);
    }

    public function test_card_style_and_icon_inputs_are_restricted_to_known_choices(): void
    {
        $kol = User::factory()->create(['role' => 'kol']);
        KolProfile::query()->create([
            'user_id' => $kol->id,
            'display_name' => 'Safe Creator',
            'slug' => 'safe-creator',
            'status' => 'draft',
        ]);

        $this->actingAs($kol)
            ->from(route('profile.edit', ['step' => 'card']))
            ->put(route('kol-card.update'), [
                'slug' => 'safe-creator',
                'card_theme' => 'custom-script',
                'card_accent' => 'invalid',
            ])
            ->assertSessionHasErrors(['card_theme', 'card_accent']);

        $this->actingAs($kol)
            ->from(route('profile.edit', ['step' => 'links']))
            ->post(route('kol-card.links.store'), [
                'title' => 'Unsafe',
                'url' => 'https://example.com',
                'icon' => 'not-an-icon',
            ])
            ->assertSessionHasErrors('icon');

        $this->assertDatabaseCount('kol_card_links', 0);
    }

    public function test_draft_slug_can_change_but_reserved_and_historical_slugs_cannot_be_claimed(): void
    {
        $kol = User::factory()->create(['role' => 'kol']);
        $profile = KolProfile::query()->create([
            'user_id' => $kol->id,
            'display_name' => 'Draft Owner',
            'slug' => 'draft-owner',
            'status' => 'draft',
        ]);
        KolProfileSlugAlias::query()->create([
            'kol_profile_id' => null,
            'slug' => 'retired-creator',
        ]);

        $this->actingAs($kol)
            ->put(route('kol-card.update'), [
                'slug' => 'draft-owner-new',
                'card_headline' => 'Updated draft',
            ])
            ->assertRedirect(route('profile.edit', ['step' => 'links']));

        $this->assertSame('draft-owner-new', $profile->fresh()->slug);

        foreach (['admin', 'retired-creator'] as $blockedSlug) {
            $this->actingAs($kol)
                ->from(route('profile.edit', ['step' => 'card']))
                ->put(route('kol-card.update'), ['slug' => $blockedSlug])
                ->assertRedirect(route('profile.edit', ['step' => 'card']))
                ->assertSessionHasErrors('slug');
        }
    }

    public function test_locked_slug_is_read_only_for_kol_but_other_card_fields_can_still_change(): void
    {
        $kol = User::factory()->create(['role' => 'kol']);
        $profile = KolProfile::query()->create([
            'user_id' => $kol->id,
            'display_name' => 'Locked Owner',
            'slug' => 'locked-owner',
            'slug_locked_at' => now(),
            'status' => 'published',
        ]);

        $this->actingAs($kol)
            ->put(route('kol-card.update'), [
                'slug' => 'hijacked-owner',
                'card_headline' => 'Should not save',
            ])
            ->assertSessionHasErrors('slug');

        $this->assertSame('locked-owner', $profile->fresh()->slug);
        $this->assertNull($profile->fresh()->card_headline);

        $this->actingAs($kol)
            ->put(route('kol-card.update'), [
                'slug' => 'locked-owner',
                'card_headline' => 'Updated safely',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Updated safely', $profile->fresh()->card_headline);
        $this->actingAs($kol)
            ->get(route('profile.edit', ['step' => 'card']))
            ->assertOk()
            ->assertSee('公開網址已鎖定')
            ->assertSee('readonly', false);
    }

    public function test_operator_slug_rename_keeps_a_permanent_redirect_and_audit_record(): void
    {
        $kol = User::factory()->create(['role' => 'kol']);
        $profile = KolProfile::query()->create([
            'user_id' => $kol->id,
            'display_name' => 'Renamed Creator',
            'slug' => 'old-creator-name',
            'slug_locked_at' => now(),
            'status' => 'published',
        ]);

        $exitCode = Artisan::call('kold:rename-kol-slug', [
            'current' => 'old-creator-name',
            'new' => 'new-creator-name',
            '--actor' => 'Karl Admin',
            '--reason' => 'Creator approved brand rename',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertSame('new-creator-name', $profile->fresh()->slug);
        $this->assertDatabaseHas('kol_profile_slug_aliases', [
            'kol_profile_id' => $profile->id,
            'slug' => 'old-creator-name',
        ]);
        $this->assertDatabaseHas('kol_profile_slug_changes', [
            'kol_profile_id' => $profile->id,
            'old_slug' => 'old-creator-name',
            'new_slug' => 'new-creator-name',
            'changed_by' => 'Karl Admin',
            'reason' => 'Creator approved brand rename',
        ]);
        $this->assertSame(1, KolProfileSlugChange::query()->count());

        $this->get(route('kol-card.show', 'old-creator-name'))
            ->assertStatus(301)
            ->assertRedirect(route('kol-card.show', 'new-creator-name'));
        $this->get(route('kol-card.show', 'new-creator-name'))->assertOk();
    }

    public function test_kol_profile_uses_structured_choices(): void
    {
        $kol = User::factory()->create(['role' => 'kol']);

        $this->actingAs($kol)
            ->get(route('profile.edit', ['step' => 'profile']))
            ->assertOk()
            ->assertSee('name="niches[]"', false)
            ->assertSee('name="regions[]"', false)
            ->assertSee('name="languages[]"', false)
            ->assertSee('name="rate_range"', false)
            ->assertSee('美妝護膚')
            ->assertSee('香港')
            ->assertSee('粵語')
            ->assertSee('HK$5,000–10,000');
    }

    public function test_kol_can_upload_a_manual_avatar_that_takes_priority_over_oauth(): void
    {
        Storage::fake('public');
        $kol = User::factory()->create([
            'role' => 'kol',
            'avatar' => 'https://example.com/oauth-avatar.jpg',
            'avatar_source' => User::AVATAR_SOURCE_OAUTH,
        ]);

        $this->actingAs($kol)
            ->put(route('profile.avatar.update'), [
                'avatar' => UploadedFile::fake()->image('creator.jpg', 600, 600)->size(300),
            ])
            ->assertRedirect(route('profile.edit', ['step' => 'profile']))
            ->assertSessionHas('status', '頭像已更新。');

        $kol->refresh();
        $this->assertSame(User::AVATAR_SOURCE_MANUAL, $kol->avatar_source);
        $this->assertStringContainsString('/storage/avatars/', (string) $kol->avatar);
        $this->assertCount(1, Storage::disk('public')->files('avatars'));
    }

    public function test_kol_can_explicitly_switch_back_to_the_primary_instagram_avatar(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('avatars/old.jpg', 'old-avatar');
        $kol = User::factory()->create([
            'role' => 'kol',
            'avatar' => 'http://localhost/storage/avatars/old.jpg',
            'avatar_source' => User::AVATAR_SOURCE_MANUAL,
        ]);
        SocialAccount::query()->create([
            'user_id' => $kol->id,
            'platform' => 'instagram',
            'account_type' => 'instagram_business',
            'external_id' => 'ig-primary',
            'handle' => 'primary.creator',
            'follower_count' => 1000,
            'is_primary' => true,
            'metrics_json' => [
                'profile_picture_url' => 'https://example.com/professional-avatar.jpg',
            ],
        ]);

        $this->actingAs($kol)
            ->post(route('profile.avatar.meta'))
            ->assertRedirect(route('profile.edit', ['step' => 'profile']))
            ->assertSessionHas('status', '已使用主要 Instagram 專業帳戶頭像。');

        $kol->refresh();
        $this->assertSame('https://example.com/professional-avatar.jpg', $kol->avatar);
        $this->assertSame(User::AVATAR_SOURCE_META, $kol->avatar_source);
        Storage::disk('public')->assertMissing('avatars/old.jpg');
    }

    public function test_kol_can_save_structured_profile_choices_and_custom_values(): void
    {
        $kol = User::factory()->create(['role' => 'kol']);

        $this->actingAs($kol)
            ->put(route('profile.update'), [
                'display_name' => 'Structured Creator',
                'bio' => '分享科技產品、汽車體驗同日常生活內容。',
                'niches' => ['科技數碼', 'other'],
                'niches_other' => '汽車、汽車',
                'regions' => ['香港', '日本'],
                'languages' => ['粵語', '英語', 'other'],
                'languages_other' => '德語',
                'age_range' => '45-54',
                'rate_range' => '5000_10000',
            ])
            ->assertRedirect(route('profile.edit', ['step' => 'card']));

        $profile = $kol->kolProfile()->firstOrFail();
        $this->assertSame(['科技數碼', '汽車'], $profile->niches);
        $this->assertSame(['香港', '日本'], $profile->regions);
        $this->assertSame(['粵語', '英語', '德語'], $profile->languages);
        $this->assertSame('45-54', $profile->age_range);
        $this->assertSame(5000, $profile->rate_min);
        $this->assertSame(10000, $profile->rate_max);
    }

    public function test_unchecking_other_removes_existing_custom_profile_values(): void
    {
        $kol = User::factory()->create(['role' => 'kol']);
        $profile = KolProfile::query()->create([
            'user_id' => $kol->id,
            'display_name' => 'Custom Creator',
            'niches' => ['科技數碼', '汽車'],
            'regions' => ['香港', '澳洲'],
            'languages' => ['粵語', '德語'],
            'status' => 'draft',
        ]);

        $this->actingAs($kol)
            ->put(route('profile.update'), [
                'display_name' => 'Custom Creator',
                'niches' => ['科技數碼'],
                'niches_other' => '汽車',
                'regions' => ['香港'],
                'regions_other' => '澳洲',
                'languages' => ['粵語'],
                'languages_other' => '德語',
            ])
            ->assertRedirect(route('profile.edit', ['step' => 'card']));

        $profile->refresh();
        $this->assertSame(['科技數碼'], $profile->niches);
        $this->assertSame(['香港'], $profile->regions);
        $this->assertSame(['粵語'], $profile->languages);
    }

    public function test_structured_profile_choices_reject_invalid_or_incomplete_values(): void
    {
        $kol = User::factory()->create(['role' => 'kol']);

        $this->actingAs($kol)
            ->from(route('profile.edit', ['step' => 'profile']))
            ->put(route('profile.update'), [
                'display_name' => 'Invalid Creator',
                'niches' => ['科技數碼', '科技數碼'],
                'regions' => ['not-a-region'],
                'languages' => ['other'],
                'languages_other' => '',
                'age_range' => '99+',
                'rate_range' => 'free-text-rate',
            ])
            ->assertRedirect(route('profile.edit', ['step' => 'profile']))
            ->assertSessionHasErrors([
                'niches.1',
                'regions.0',
                'languages_other',
                'age_range',
                'rate_range',
            ]);

        $this->assertNull($kol->kolProfile);
    }

    public function test_legacy_profile_values_are_mapped_to_structured_choices(): void
    {
        $kol = User::factory()->create(['role' => 'kol']);
        $profile = KolProfile::query()->create([
            'user_id' => $kol->id,
            'display_name' => 'Legacy Creator',
            'niches' => ['IT科技'],
            'regions' => ['香港'],
            'languages' => ['英文'],
            'rate_min' => 3000,
            'rate_max' => 12000,
            'status' => 'draft',
        ]);

        $this->actingAs($kol)
            ->get(route('profile.edit', ['step' => 'profile']))
            ->assertOk()
            ->assertSee('value="科技數碼" checked', false)
            ->assertSee('value="英語" checked', false)
            ->assertSee('保留現有：HK$3,000–12,000');

        $this->actingAs($kol)
            ->put(route('profile.update'), [
                'display_name' => 'Legacy Creator',
                'niches' => ['科技數碼'],
                'regions' => ['香港'],
                'languages' => ['英語'],
                'rate_range' => 'existing',
            ])
            ->assertRedirect();

        $profile->refresh();
        $this->assertSame(['科技數碼'], $profile->niches);
        $this->assertSame(['英語'], $profile->languages);
        $this->assertSame(3000, $profile->rate_min);
        $this->assertSame(12000, $profile->rate_max);
    }

    public function test_card_requires_an_active_link_before_publish_and_can_be_unpublished(): void
    {
        $kol = User::factory()->create(['role' => 'kol']);
        $profile = KolProfile::query()->create([
            'user_id' => $kol->id,
            'display_name' => 'Publish Owner',
            'slug' => 'publish-owner',
            'status' => 'draft',
        ]);

        $this->actingAs($kol)
            ->post(route('kol-card.publish'))
            ->assertRedirect(route('profile.edit', ['step' => 'preview']))
            ->assertSessionHasErrors('publish');

        $this->assertSame('draft', $profile->fresh()->status);

        $profile->cardLinks()->create([
            'title' => 'Instagram',
            'url' => 'https://instagram.com/publish-owner',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($kol)
            ->get(route('kol-card.preview'))
            ->assertOk()
            ->assertSee('私人預覽');

        $this->actingAs($kol)
            ->post(route('kol-card.publish'), ['confirm_slug_lock' => '1'])
            ->assertRedirect(route('profile.edit', ['step' => 'preview']))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'KOL 卡片已發布，公開網址亦已鎖定。');

        $this->assertNotNull($profile->fresh()->slug_locked_at);

        $this->get(route('kol-card.show', 'publish-owner'))->assertOk();

        $this->actingAs($kol)->post(route('kol-card.unpublish'))->assertRedirect();
        $this->get(route('kol-card.show', 'publish-owner'))->assertNotFound();

        $this->actingAs($kol)
            ->put(route('kol-card.update'), ['slug' => 'publish-owner-renamed'])
            ->assertSessionHasErrors('slug');
        $this->assertSame('publish-owner', $profile->fresh()->slug);
    }

    public function test_first_publish_requires_slug_lock_confirmation(): void
    {
        $kol = User::factory()->create(['role' => 'kol']);
        $profile = KolProfile::query()->create([
            'user_id' => $kol->id,
            'display_name' => 'Confirmation Owner',
            'slug' => 'confirmation-owner',
            'status' => 'draft',
        ]);
        $profile->cardLinks()->create([
            'title' => 'Instagram',
            'url' => 'https://instagram.com/confirmation-owner',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($kol)
            ->from(route('profile.edit', ['step' => 'preview']))
            ->post(route('kol-card.publish'))
            ->assertRedirect(route('profile.edit', ['step' => 'preview']))
            ->assertSessionHasErrors('confirm_slug_lock');

        $profile->refresh();
        $this->assertSame('draft', $profile->status);
        $this->assertNull($profile->slug_locked_at);
    }

    public function test_kol_can_create_card_links_from_social_accounts(): void
    {
        $kol = User::factory()->create(['role' => 'kol']);
        KolProfile::query()->create([
            'user_id' => $kol->id,
            'display_name' => 'Social Link Owner',
            'status' => 'published',
        ]);
        SocialAccount::query()->create([
            'user_id' => $kol->id,
            'platform' => 'instagram',
            'account_type' => 'manual',
            'handle' => 'social.owner',
            'follower_count' => 1234,
            'metrics_json' => [],
        ]);

        $this->actingAs($kol)
            ->post(route('kol-card.links.social'))
            ->assertRedirect()
            ->assertSessionHas('status', '已加入 1 個社群連結。');

        $this->assertDatabaseHas('kol_card_links', [
            'title' => 'Instagram',
            'url' => 'https://instagram.com/social.owner',
            'type' => 'instagram',
            'is_active' => true,
        ]);
    }

    public function test_ai_tags_are_suggested_before_kol_approval(): void
    {
        $kol = User::factory()->create(['role' => 'kol']);
        $profile = KolProfile::query()->create([
            'user_id' => $kol->id,
            'display_name' => 'Beauty KOL',
            'bio' => '香港美妝護膚分享',
            'niches' => ['美妝'],
            'regions' => ['香港'],
            'status' => 'published',
        ]);

        $this->actingAs($kol)
            ->post(route('ai-tags.generate'))
            ->assertRedirect();

        $tag = $profile->aiTags()->where('label', '美妝')->first()
            ?: $profile->aiTags()->where('label', '美妝護膚')->first();

        $this->assertNotNull($tag);
        $this->assertSame('suggested', $tag->status);

        $this->actingAs($kol)
            ->post(route('ai-tags.approve', $tag))
            ->assertRedirect();

        $this->assertSame('approved', $tag->fresh()->status);
    }

    public function test_invalid_ai_response_falls_back_to_safe_tags(): void
    {
        config()->set('services.openai.api_key', 'test-key');
        Http::fake([
            '*' => Http::response(['choices' => [['message' => ['content' => 'not-json']]]], 200),
        ]);

        $kol = User::factory()->create(['role' => 'kol']);
        $profile = KolProfile::query()->create([
            'user_id' => $kol->id,
            'display_name' => 'Fallback KOL',
            'bio' => '香港美妝短影音測評',
            'niches' => ['美妝'],
            'regions' => ['香港'],
            'status' => 'draft',
        ]);

        $this->actingAs($kol)->post(route('ai-tags.generate'))->assertRedirect();

        $this->assertDatabaseHas('kol_ai_tags', [
            'kol_profile_id' => $profile->id,
            'label' => '美妝護膚',
            'source' => 'fallback',
            'status' => 'suggested',
        ]);
    }

    public function test_kol_can_approve_all_suggested_tags(): void
    {
        $kol = User::factory()->create(['role' => 'kol']);
        $profile = KolProfile::query()->create([
            'user_id' => $kol->id,
            'display_name' => 'Tag Owner',
            'status' => 'published',
        ]);
        KolAiTag::query()->create([
            'kol_profile_id' => $profile->id,
            'category' => 'content',
            'label' => '美妝護膚',
            'status' => 'suggested',
            'confidence' => 80,
        ]);
        KolAiTag::query()->create([
            'kol_profile_id' => $profile->id,
            'category' => 'region',
            'label' => '香港',
            'status' => 'suggested',
            'confidence' => 70,
        ]);

        $this->actingAs($kol)
            ->post(route('ai-tags.approve-all'))
            ->assertRedirect()
            ->assertSessionHas('status', '已確認 2 個 AI 標籤。');

        $this->assertSame(2, $profile->aiTags()->where('status', 'approved')->count());
    }

    public function test_brand_brief_search_uses_only_approved_tags(): void
    {
        $brand = User::factory()->create(['role' => 'brand']);
        $approvedKol = User::factory()->create(['role' => 'kol']);
        $suggestedKol = User::factory()->create(['role' => 'kol']);

        $approvedProfile = KolProfile::query()->create([
            'user_id' => $approvedKol->id,
            'display_name' => 'Approved Beauty KOL',
            'bio' => '香港美妝護膚分享',
            'slug' => 'approved-beauty',
            'status' => 'published',
        ]);
        $suggestedProfile = KolProfile::query()->create([
            'user_id' => $suggestedKol->id,
            'display_name' => 'Suggested Beauty KOL',
            'bio' => '香港美妝護膚分享',
            'status' => 'published',
        ]);

        KolAiTag::query()->create([
            'kol_profile_id' => $approvedProfile->id,
            'category' => 'content',
            'label' => '美妝護膚',
            'status' => 'approved',
            'confidence' => 80,
        ]);
        KolAiTag::query()->create([
            'kol_profile_id' => $suggestedProfile->id,
            'category' => 'content',
            'label' => '美妝護膚',
            'status' => 'suggested',
            'confidence' => 80,
        ]);

        $this->actingAs($brand)
            ->get(route('match.index', ['brief' => '香港護膚品牌想搵美妝 KOL']))
            ->assertOk()
            ->assertSee('Approved Beauty KOL')
            ->assertSee('符合「美妝護膚」')
            ->assertDontSee('Suggested Beauty KOL');
    }

    public function test_brand_matching_separates_primary_and_broad_related_candidates(): void
    {
        $brand = User::factory()->create(['role' => 'brand']);
        $beautyKol = User::factory()->create(['role' => 'kol']);
        $travelKol = User::factory()->create(['role' => 'kol']);

        $beauty = KolProfile::query()->create([
            'user_id' => $beautyKol->id,
            'display_name' => 'Beauty First',
            'status' => 'published',
        ]);
        $travel = KolProfile::query()->create([
            'user_id' => $travelKol->id,
            'display_name' => 'Travel Related',
            'status' => 'published',
        ]);

        foreach ([
            [$beauty, 'content', '美妝護膚'],
            [$beauty, 'region', '香港'],
            [$travel, 'content', '旅遊生活'],
            [$travel, 'region', '香港'],
        ] as [$profile, $category, $label]) {
            KolAiTag::query()->create([
                'kol_profile_id' => $profile->id,
                'category' => $category,
                'label' => $label,
                'status' => 'approved',
                'confidence' => 80,
            ]);
        }

        $this->actingAs($brand)
            ->get(route('match.index', [
                'product' => '香港護膚產品',
                'region' => '香港',
            ]))
            ->assertOk()
            ->assertSeeInOrder(['主要推薦', 'Beauty First', '其他相關', 'Travel Related'])
            ->assertSee('內容類別符合「美妝護膚」')
            ->assertSee('地區符合「香港」');
    }

    public function test_brand_can_search_with_only_a_maximum_budget(): void
    {
        $brand = User::factory()->create(['role' => 'brand']);
        $kol = User::factory()->create(['role' => 'kol']);
        $profile = KolProfile::query()->create([
            'user_id' => $kol->id,
            'display_name' => 'Budget KOL',
            'rate_min' => 2000,
            'rate_max' => 8000,
            'status' => 'published',
        ]);
        KolAiTag::query()->create([
            'kol_profile_id' => $profile->id,
            'category' => 'content',
            'label' => '美妝護膚',
            'status' => 'approved',
            'confidence' => 80,
        ]);

        $this->actingAs($brand)
            ->get(route('match.index', ['product' => '護膚產品', 'budget_max' => 10000]))
            ->assertOk()
            ->assertSee('Budget KOL')
            ->assertSee('參考報價與預算有重疊');
    }

    public function test_only_kols_can_preview_and_publish_cards(): void
    {
        $brand = User::factory()->create(['role' => 'brand']);

        $this->actingAs($brand)->get(route('kol-card.preview'))->assertForbidden();
        $this->actingAs($brand)->post(route('kol-card.publish'))->assertForbidden();
    }

    public function test_non_owner_cannot_approve_kol_tag(): void
    {
        $owner = User::factory()->create(['role' => 'kol']);
        $other = User::factory()->create(['role' => 'kol']);
        $profile = KolProfile::query()->create([
            'user_id' => $owner->id,
            'display_name' => 'Owner',
            'status' => 'published',
        ]);
        $tag = KolAiTag::query()->create([
            'kol_profile_id' => $profile->id,
            'category' => 'content',
            'label' => '美妝護膚',
            'status' => 'suggested',
            'confidence' => 80,
        ]);

        $this->actingAs($other)
            ->post(route('ai-tags.approve', $tag))
            ->assertForbidden();
    }
}
