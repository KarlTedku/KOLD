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
