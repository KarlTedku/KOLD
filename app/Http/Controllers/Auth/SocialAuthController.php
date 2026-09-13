<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SocialSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class SocialAuthController extends Controller
{
    /** @var array<int, string> */
    protected array $providers = ['google', 'facebook'];

    public function redirect(string $provider): RedirectResponse
    {
        $this->assertProvider($provider);

        if (! $this->providerConfigured($provider)) {
            return redirect()
                ->route('home')
                ->with('error', strtoupper($provider).' SSO 尚未設定 Client ID／Secret，請先使用示範登入。');
        }

        $driver = Socialite::driver($provider);

        if ($provider === 'facebook') {
            $driver->scopes(['email', 'public_profile']);
        }

        if ($provider === 'google') {
            $driver->scopes(['openid', 'profile', 'email']);
        }

        return $driver->redirect();
    }

    public function callback(string $provider, Request $request, SocialSyncService $sync): RedirectResponse
    {
        $this->assertProvider($provider);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (InvalidStateException|\Throwable $e) {
            return redirect()->route('home')->with('error', '登入失敗，請再試一次。');
        }

        if ($provider === 'facebook' && $request->session()->get('social_connect.provider') === 'facebook') {
            return $this->handleFacebookConnectCallback($request, $sync, (string) $socialUser->token);
        }

        $user = User::query()
            ->where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if (! $user && $socialUser->getEmail()) {
            $user = User::query()->where('email', $socialUser->getEmail())->first();
        }

        if (! $user) {
            $user = new User;
            $user->email = $socialUser->getEmail() ?: $provider.'_'.$socialUser->getId().'@users.kold.local';
        }

        $user->fill([
            'name' => $socialUser->getName() ?: ($socialUser->getNickname() ?: 'KOLD User'),
            'avatar' => $socialUser->getAvatar(),
            'provider' => $provider,
            'provider_id' => $socialUser->getId(),
            'email_verified_at' => now(),
        ]);
        $user->save();

        Auth::login($user, true);

        return $user->hasRole()
            ? redirect()->route('dashboard')
            : redirect()->route('onboarding.role');
    }

    public function demoLogin(Request $request): RedirectResponse
    {
        abort_unless(config('kold.demo_login_enabled'), 404);

        $role = $request->validate([
            'role' => ['required', 'in:kol,brand'],
        ])['role'];

        $email = $role === 'kol' ? 'kol.demo@kold.local' : 'brand.demo@kold.local';

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $role === 'kol' ? '示範 KOL' : '示範品牌',
                'role' => $role,
                'provider' => 'demo',
                'provider_id' => 'demo-'.$role,
                'email_verified_at' => now(),
                'avatar' => null,
            ]
        );

        if (! $user->role) {
            $user->role = $role;
            $user->save();
        }

        Auth::login($user, true);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    protected function assertProvider(string $provider): void
    {
        abort_unless(in_array($provider, $this->providers, true), 404);
    }

    protected function providerConfigured(string $provider): bool
    {
        $clientId = config("services.{$provider}.client_id");
        $clientSecret = config("services.{$provider}.client_secret");

        return filled($clientId) && filled($clientSecret)
            && ! str_starts_with((string) $clientId, 'REPLACE_');
    }

    protected function handleFacebookConnectCallback(Request $request, SocialSyncService $sync, string $accessToken): RedirectResponse
    {
        if (! Auth::check()) {
            $request->session()->forget('social_connect');

            return redirect()->route('home')->with('error', '請先登入 KOLD，再連結 Meta／IG。');
        }

        try {
            $candidates = $sync->facebookPageInstagramCandidates($accessToken);
        } catch (\Throwable $e) {
            $request->session()->forget('social_connect');

            return redirect()->route('social.index')->with('error', $e->getMessage());
        }

        $connectable = collect($candidates)
            ->filter(fn (array $candidate) => is_array($candidate['instagram'] ?? null))
            ->values()
            ->all();

        if ($connectable === []) {
            $request->session()->forget('social_connect');

            return redirect()
                ->route('social.index')
                ->with('error', '此 Facebook 帳號暫時未找到已連結的 Instagram Business／Creator 帳號。請確認 IG 已連到 Facebook Page。');
        }

        $request->session()->put('meta_connect_candidates', $connectable);

        return redirect()->route('social.meta.select');
    }
}
