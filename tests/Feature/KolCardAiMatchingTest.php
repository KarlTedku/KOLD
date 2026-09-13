<?php

namespace Tests\Feature;

use App\Models\KolAiTag;
use App\Models\KolProfile;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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
            ->post(route('kol-card.publish'))
            ->assertRedirect(route('profile.edit', ['step' => 'preview']))
            ->assertSessionHasNoErrors();

        $this->get(route('kol-card.show', 'publish-owner'))->assertOk();

        $this->actingAs($kol)->post(route('kol-card.unpublish'))->assertRedirect();
        $this->get(route('kol-card.show', 'publish-owner'))->assertNotFound();
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
