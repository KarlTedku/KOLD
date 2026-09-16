<?php

namespace Tests\Feature;

use App\Models\BrandProfile;
use App\Models\BrandProject;
use App\Models\Collaboration;
use App\Models\ContactRequest;
use App\Models\KolProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectMarketplaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_brand_can_create_a_structured_project_brief(): void
    {
        $brand = $this->brand();

        $response = $this->actingAs($brand)->post(route('projects.store'), [
            'title' => 'Summer Skincare Launch',
            'campaign_objective' => 'product_launch',
            'brief' => '介紹夏季防曬產品，並以真實日常使用體驗呈現產品賣點。',
            'niches' => ['美妝護膚', 'other'],
            'niches_other' => '戶外活動、戶外活動',
            'regions' => ['香港', 'other'],
            'regions_other' => '澳洲',
            'platforms' => ['instagram', 'youtube'],
            'target_audience' => '香港 25–34 歲、關注護膚與健康生活嘅上班族。',
            'collaboration_formats' => ['short_video', 'product_review'],
            'compensation_type' => 'paid',
            'budget_min' => 5000,
            'budget_max' => 12000,
            'deliverables' => '一條短影片及一次產品試用分享。',
            'usage_rights' => 'brand_repost',
            'application_deadline' => '2026-10-01',
            'campaign_start_date' => '2026-10-10',
            'campaign_end_date' => '2026-10-31',
        ]);

        $project = BrandProject::query()->where('title', 'Summer Skincare Launch')->firstOrFail();

        $response->assertRedirect(route('projects.edit', $project));
        $this->assertSame('draft', $project->status);
        $this->assertSame(['美妝護膚', '戶外活動'], $project->niches);
        $this->assertSame(['香港', '澳洲'], $project->regions);
        $this->assertSame(['instagram', 'youtube'], $project->platforms);
        $this->assertSame(['short_video', 'product_review'], $project->collaboration_formats);
        $this->assertSame('product_launch', $project->campaign_objective);
        $this->assertSame('brand_repost', $project->usage_rights);
    }

    public function test_project_form_exposes_four_steps_and_structured_choices(): void
    {
        $this->actingAs($this->brand())
            ->get(route('projects.create'))
            ->assertOk()
            ->assertSee('步驟 1 / 4')
            ->assertSee('步驟 2 / 4')
            ->assertSee('步驟 3 / 4')
            ->assertSee('步驟 4 / 4')
            ->assertSee('美妝護膚')
            ->assertSee('產品試用／評測')
            ->assertSee('草稿資料摘要');
    }

    public function test_structured_project_fields_reject_invalid_and_duplicate_values(): void
    {
        $this->actingAs($this->brand())
            ->from(route('projects.create'))
            ->post(route('projects.store'), [
                'title' => 'Invalid Campaign',
                'brief' => '呢段合作簡介有足夠長度，但其他結構化欄位並不合法。',
                'campaign_objective' => 'not-valid',
                'niches' => ['美妝護膚', '美妝護膚'],
                'platforms' => ['tiktok'],
                'compensation_type' => 'crypto',
                'budget_min' => 10000,
                'budget_max' => 5000,
                'campaign_start_date' => '2026-11-10',
                'campaign_end_date' => '2026-11-01',
            ])
            ->assertRedirect(route('projects.create'))
            ->assertSessionHasErrors([
                'campaign_objective',
                'niches.1',
                'platforms.0',
                'compensation_type',
                'budget_max',
                'campaign_end_date',
            ]);

        $this->assertDatabaseMissing('brand_projects', ['title' => 'Invalid Campaign']);
    }

    public function test_other_selection_requires_a_custom_value(): void
    {
        $this->actingAs($this->brand())
            ->post(route('projects.store'), [
                'title' => 'Custom Category Campaign',
                'brief' => '呢段合作簡介有足夠長度，但其他類別未有填寫內容。',
                'niches' => ['other'],
                'niches_other' => '',
            ])
            ->assertSessionHasErrors('niches_other');
    }

    public function test_validation_error_reopens_the_relevant_step_with_old_input(): void
    {
        $brand = $this->brand();

        $response = $this->actingAs($brand)
            ->from(route('projects.create'))
            ->followingRedirects()
            ->post(route('projects.store'), [
                'title' => 'Budget Error Campaign',
                'brief' => '呢段合作簡介有足夠長度，用嚟確認錯誤會返回正確步驟。',
                'budget_min' => 10000,
                'budget_max' => 5000,
            ]);

        $response->assertOk()
            ->assertSee('data-error-step="3"', false)
            ->assertSee('value="10000"', false)
            ->assertSee('value="5000"', false);
    }

    public function test_legacy_project_payload_and_public_detail_remain_compatible(): void
    {
        $brand = $this->brand();

        $this->actingAs($brand)->post(route('projects.store'), [
            'title' => 'Legacy Brief',
            'brief' => '沿用舊有最小欄位仍然可以建立合作項目草稿。',
        ])->assertRedirect();

        $project = $this->project($brand, [
            'title' => 'Legacy Public Project',
            'slug' => 'legacy-public-project',
        ]);

        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertDontSee('合作重點')
            ->assertDontSee('內容使用權')
            ->assertDontSee('報酬方式');
    }

    public function test_published_projects_are_public_but_drafts_are_private(): void
    {
        $brand = $this->brand();
        $published = $this->project($brand, ['title' => 'Skincare Launch', 'slug' => 'skincare-launch']);
        $draft = $this->project($brand, ['title' => 'Private Draft', 'slug' => 'private-draft', 'status' => 'draft']);

        $this->get(route('projects.index'))
            ->assertOk()
            ->assertSee('Skincare Launch')
            ->assertDontSee('Private Draft');

        $this->get(route('projects.show', $published))->assertOk()->assertSee('登入後申請');
        $this->get(route('projects.show', $draft))->assertNotFound();
        $this->actingAs($brand)->get(route('projects.show', $draft))->assertOk();
    }

    public function test_project_marketplace_filters_public_projects(): void
    {
        $brand = $this->brand();
        $matching = $this->project($brand, [
            'title' => 'Autumn Glow Campaign',
            'slug' => 'autumn-glow-campaign',
            'niches' => ['美妝護膚'],
            'regions' => ['香港'],
            'platforms' => ['instagram'],
            'budget_min' => 8000,
            'budget_max' => 12000,
            'application_deadline' => today()->addDays(7),
        ]);
        $this->project($brand, [
            'title' => 'Autumn Fitness Campaign',
            'slug' => 'autumn-fitness-campaign',
            'niches' => ['健身健康'],
            'regions' => ['香港'],
            'platforms' => ['youtube'],
            'budget_min' => 12000,
            'budget_max' => 18000,
            'application_deadline' => today()->addDays(5),
        ]);
        $this->project($brand, [
            'title' => 'Expired Autumn Glow Campaign',
            'slug' => 'expired-autumn-glow-campaign',
            'niches' => ['美妝護膚'],
            'regions' => ['香港'],
            'platforms' => ['instagram'],
            'budget_min' => 10000,
            'budget_max' => 15000,
            'application_deadline' => today()->subDay(),
        ]);

        $this->get(route('projects.index', [
            'q' => 'Glow',
            'niche' => '美妝護膚',
            'region' => '香港',
            'platform' => 'instagram',
            'budget_min' => 10000,
            'open' => 1,
        ]))
            ->assertOk()
            ->assertSee($matching->title)
            ->assertDontSee('Autumn Fitness Campaign')
            ->assertDontSee('Expired Autumn Glow Campaign')
            ->assertSee('只顯示可申請')
            ->assertSee('1 個合作機會');
    }

    public function test_project_marketplace_can_sort_by_deadline(): void
    {
        $brand = $this->brand();
        $this->project($brand, [
            'title' => 'Later Deadline Campaign',
            'slug' => 'later-deadline-campaign',
            'application_deadline' => today()->addDays(14),
        ]);
        $this->project($brand, [
            'title' => 'Sooner Deadline Campaign',
            'slug' => 'sooner-deadline-campaign',
            'application_deadline' => today()->addDays(3),
        ]);

        $this->get(route('projects.index', ['sort' => 'deadline']))
            ->assertOk()
            ->assertSeeInOrder(['Sooner Deadline Campaign', 'Later Deadline Campaign']);
    }

    public function test_sample_projects_hide_brand_identity_on_public_pages(): void
    {
        $brand = $this->brand();
        $sample = $this->project($brand, [
            'title' => 'Sample Fitness Campaign',
            'slug' => 'sample-fitness-campaign',
            'brief' => '希望招募健身創作者分享訓練體驗。',
            'is_sample' => true,
        ]);

        $this->get(route('projects.index'))
            ->assertOk()
            ->assertSee($sample->title)
            ->assertDontSee('Beta Brand');

        $this->get(route('projects.show', $sample))
            ->assertOk()
            ->assertSee($sample->brief)
            ->assertDontSee('Beta Brand');
    }

    public function test_real_projects_continue_to_show_brand_identity(): void
    {
        $project = $this->project($this->brand(), [
            'title' => 'Real Brand Campaign',
            'slug' => 'real-brand-campaign',
        ]);

        $this->get(route('projects.index'))->assertSee('Beta Brand');
        $this->get(route('projects.show', $project))->assertSee('Beta Brand');
    }

    public function test_kol_must_log_in_to_apply_and_cannot_apply_twice(): void
    {
        $project = $this->project($this->brand());
        $kol = $this->kol();

        $this->post(route('projects.apply', $project), [
            'pitch' => '我可以用短片示範產品日常使用場景，並配合品牌檔期發佈內容。',
        ])->assertRedirect(route('home').'#start');

        $payload = [
            'pitch' => '我可以用短片示範產品日常使用場景，並配合品牌檔期發佈內容。',
            'proposed_rate' => 3500,
        ];

        $this->actingAs($kol)->post(route('projects.apply', $project), $payload)
            ->assertRedirect()
            ->assertSessionHas('status', '申請已送出。');

        $this->actingAs($kol)->post(route('projects.apply', $project), $payload)
            ->assertSessionHas('error', '你已經申請過呢個 Project。');

        $this->assertDatabaseCount('project_applications', 1);
    }

    public function test_kol_can_apply_with_a_short_pitch(): void
    {
        $project = $this->project($this->brand());
        $kol = $this->kol();

        $this->actingAs($kol)
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('合作構思（簡單填寫即可）')
            ->assertDontSee('minlength="20"', false);

        $this->actingAs($kol)
            ->post(route('projects.apply', $project), [
                'pitch' => 'interested',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', '申請已送出。');

        $this->assertDatabaseHas('project_applications', [
            'brand_project_id' => $project->id,
            'kol_user_id' => $kol->id,
            'pitch' => 'interested',
            'status' => 'pending',
        ]);
    }

    public function test_brand_can_only_review_applications_for_own_project(): void
    {
        $owner = $this->brand();
        $otherBrand = $this->brand();
        $project = $this->project($owner);
        $application = $this->application($project, $this->kol());

        $this->actingAs($otherBrand)
            ->get(route('projects.applications', $project))
            ->assertForbidden();

        $this->actingAs($otherBrand)
            ->post(route('applications.accept', $application))
            ->assertForbidden();

        $this->actingAs($owner)
            ->get(route('projects.applications', $project))
            ->assertOk()
            ->assertSee('KOL Applicant');
    }

    public function test_accepting_an_application_creates_conversation_and_collaboration(): void
    {
        $brand = $this->brand();
        $project = $this->project($brand);
        $application = $this->application($project, $this->kol());

        $response = $this->actingAs($brand)->post(route('applications.accept', $application));

        $application->refresh();
        $collaboration = Collaboration::query()->firstOrFail();
        $response->assertRedirect(route('conversations.show', $collaboration->conversation_id));
        $this->assertSame('accepted', $application->status);
        $this->assertSame('negotiating', $collaboration->status);
        $this->assertDatabaseHas('messages', ['body' => $application->pitch]);
    }

    public function test_collaboration_status_advances_in_order_and_only_by_a_participant(): void
    {
        $brand = $this->brand();
        $kol = $this->kol();
        $outsider = $this->brand();
        $collaboration = Collaboration::query()->create([
            'brand_user_id' => $brand->id,
            'kol_user_id' => $kol->id,
            'updated_by_user_id' => $brand->id,
            'title' => 'Launch Campaign',
            'status' => 'negotiating',
        ]);

        $this->actingAs($outsider)->put(route('collaborations.update', $collaboration), ['status' => 'confirmed'])->assertForbidden();
        $this->actingAs($brand)->put(route('collaborations.update', $collaboration), ['status' => 'in_progress'])->assertStatus(422);
        $this->actingAs($brand)->put(route('collaborations.update', $collaboration), ['status' => 'confirmed'])->assertRedirect();
        $this->actingAs($kol)->put(route('collaborations.update', $collaboration), ['status' => 'in_progress'])->assertRedirect();
        $this->actingAs($brand)->put(route('collaborations.update', $collaboration), ['status' => 'completed'])->assertRedirect();

        $this->assertSame('completed', $collaboration->fresh()->status);
    }

    public function test_accepting_a_direct_invitation_also_creates_a_collaboration(): void
    {
        $brand = $this->brand();
        $kol = $this->kol();
        $contact = ContactRequest::query()->create([
            'from_user_id' => $brand->id,
            'to_user_id' => $kol->id,
            'message' => '想邀請你參與護膚品牌短片合作。',
            'status' => 'pending',
        ]);

        $this->actingAs($kol)->post(route('contact.accept', $contact))->assertRedirect();

        $this->assertDatabaseHas('collaborations', [
            'brand_user_id' => $brand->id,
            'kol_user_id' => $kol->id,
            'contact_request_id' => $contact->id,
            'status' => 'negotiating',
        ]);
    }

    private function brand(): User
    {
        $brand = User::factory()->create(['role' => 'brand']);
        BrandProfile::query()->create([
            'user_id' => $brand->id,
            'company_name' => 'Beta Brand',
            'status' => 'published',
        ]);

        return $brand;
    }

    private function kol(): User
    {
        $kol = User::factory()->create(['role' => 'kol']);
        KolProfile::query()->create([
            'user_id' => $kol->id,
            'display_name' => 'KOL Applicant',
            'slug' => 'kol-applicant-'.$kol->id,
            'status' => 'published',
        ]);

        return $kol;
    }

    /** @param array<string, mixed> $overrides */
    private function project(User $brand, array $overrides = []): BrandProject
    {
        return BrandProject::query()->create(array_merge([
            'brand_user_id' => $brand->id,
            'title' => 'Beauty Product Launch',
            'slug' => 'beauty-launch-'.$brand->id,
            'brief' => '尋找香港美妝 KOL 製作產品體驗短片及分享真實使用感受。',
            'niches' => ['美妝'],
            'regions' => ['香港'],
            'platforms' => ['instagram'],
            'status' => 'published',
        ], $overrides));
    }

    private function application(BrandProject $project, User $kol): ProjectApplication
    {
        return ProjectApplication::query()->create([
            'brand_project_id' => $project->id,
            'kol_user_id' => $kol->id,
            'pitch' => '我可以製作日常護膚短片，並向香港年輕受眾分享真實體驗。',
            'proposed_rate' => 3500,
            'status' => 'pending',
        ]);
    }
}
