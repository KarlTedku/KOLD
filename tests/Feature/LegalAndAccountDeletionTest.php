<?php

namespace Tests\Feature;

use App\Models\DataDeletionRequest;
use App\Models\KolProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalAndAccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_legal_pages_are_public(): void
    {
        $this->get(route('privacy'))->assertOk()->assertSee('私隱政策');
        $this->get(route('terms'))->assertOk()->assertSee('使用條款');
        $this->get(route('data-deletion.instructions'))->assertOk()->assertSee('刪除 KOLD 資料');
    }

    public function test_demo_login_is_hidden_and_blocked_when_disabled(): void
    {
        config()->set('kold.demo_login_enabled', false);

        $this->get(route('home'))->assertOk()->assertDontSee('示範登入');
        $this->post(route('auth.demo'), ['role' => 'kol'])->assertNotFound();
        $this->assertDatabaseMissing('users', ['provider' => 'demo']);
    }

    public function test_user_can_permanently_delete_their_account_and_related_profile(): void
    {
        $user = User::factory()->create([
            'role' => 'kol',
            'provider' => 'facebook',
            'provider_id' => 'meta-123',
        ]);
        $profile = KolProfile::query()->create([
            'user_id' => $user->id,
            'display_name' => 'Delete Me',
            'slug' => 'delete-me',
            'status' => 'published',
        ]);
        $profile->cardLinks()->create([
            'title' => 'Instagram',
            'url' => 'https://instagram.com/delete-me',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->delete(route('account.destroy'), [
            'confirmation' => 'DELETE',
        ]);

        $deletionRequest = DataDeletionRequest::query()->sole();

        $response->assertRedirect(route('data-deletion.status', $deletionRequest->confirmation_code));
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('kol_profiles', ['id' => $profile->id]);
        $this->assertDatabaseMissing('kol_card_links', ['kol_profile_id' => $profile->id]);
        $this->get(route('data-deletion.status', $deletionRequest->confirmation_code))
            ->assertOk()
            ->assertSee($deletionRequest->confirmation_code);
    }

    public function test_account_deletion_requires_exact_confirmation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('account.delete'))
            ->delete(route('account.destroy'), ['confirmation' => 'delete'])
            ->assertRedirect(route('account.delete'))
            ->assertSessionHasErrors('confirmation');

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_valid_meta_signed_request_deletes_matching_facebook_user(): void
    {
        config()->set('services.facebook.client_secret', 'meta-secret');
        $user = User::factory()->create([
            'provider' => 'facebook',
            'provider_id' => '998877',
        ]);

        $response = $this->postJson(route('data-deletion.meta'), [
            'signed_request' => $this->signedRequest([
                'algorithm' => 'HMAC-SHA256',
                'user_id' => '998877',
            ], 'meta-secret'),
        ]);

        $response->assertOk()->assertJsonStructure(['url', 'confirmation_code']);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseHas('data_deletion_requests', [
            'source' => 'meta',
            'status' => 'completed',
        ]);
    }

    public function test_invalid_meta_signed_request_is_rejected(): void
    {
        config()->set('services.facebook.client_secret', 'correct-secret');

        $this->postJson(route('data-deletion.meta'), [
            'signed_request' => $this->signedRequest([
                'algorithm' => 'HMAC-SHA256',
                'user_id' => '998877',
            ], 'wrong-secret'),
        ])->assertBadRequest();

        $this->assertDatabaseCount('data_deletion_requests', 0);
    }

    /** @param array<string, string> $payload */
    protected function signedRequest(array $payload, string $secret): string
    {
        $encodedPayload = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
        $signature = hash_hmac('sha256', $encodedPayload, $secret, true);

        return $this->base64UrlEncode($signature).'.'.$encodedPayload;
    }

    protected function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
