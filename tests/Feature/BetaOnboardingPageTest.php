<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BetaOnboardingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_beta_page_has_real_login_and_project_links(): void
    {
        $this->get(route('beta.index'))
            ->assertOk()
            ->assertSee('建立你嘅 KOL 專屬頁面')
            ->assertSee(route('auth.redirect', 'google'), false)
            ->assertSee(route('auth.redirect', 'facebook'), false)
            ->assertSee(route('projects.index'), false);
    }

    public function test_signed_in_user_gets_a_continue_action(): void
    {
        $user = User::factory()->create(['role' => 'kol']);

        $this->actingAs($user)
            ->get(route('beta.index'))
            ->assertOk()
            ->assertSee('繼續設定我的 KOLD')
            ->assertSee(route('dashboard'), false)
            ->assertDontSee('使用 Meta 登入');
    }
}
