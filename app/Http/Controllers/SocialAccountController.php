<?php

namespace App\Http\Controllers;

use App\Services\SocialSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;

class SocialAccountController extends Controller
{
    public function index(): View
    {
        $accounts = auth()->user()->socialAccounts()->orderBy('platform')->get();

        return view('social.index', compact('accounts'));
    }

    public function storeManual(Request $request, SocialSyncService $sync): RedirectResponse
    {
        $data = $request->validate([
            'platform' => ['required', 'in:instagram,facebook,youtube'],
            'handle' => ['required', 'string', 'max:120'],
            'follower_count' => ['nullable', 'integer', 'min:0'],
        ]);

        $sync->upsertManual($request->user(), $data['platform'], [
            'handle' => $data['handle'],
            'follower_count' => $data['follower_count'] ?? 0,
            'metrics_json' => ['source' => 'manual'],
        ]);

        return back()->with('status', '社群帳號已儲存。');
    }

    public function redirectConnect(string $platform): RedirectResponse
    {
        abort_unless(in_array($platform, ['youtube', 'facebook'], true), 404);

        if ($platform === 'youtube') {
            if (! filled(config('services.google.client_id'))) {
                return back()->with('error', '尚未設定 Google OAuth，請先用手動填寫。');
            }

            return Socialite::driver('google')
                ->scopes(['https://www.googleapis.com/auth/youtube.readonly'])
                ->with(['access_type' => 'offline', 'prompt' => 'consent', 'state' => 'connect_youtube'])
                ->redirect();
        }

        if (! filled(config('services.facebook.client_id'))) {
            return back()->with('error', '尚未設定 Meta OAuth，請先用手動填寫。');
        }

        return Socialite::driver('facebook')
            ->scopes(['email', 'public_profile', 'pages_show_list', 'instagram_basic'])
            ->with(['state' => 'connect_instagram'])
            ->redirect();
    }

    public function destroy(string $platform): RedirectResponse
    {
        auth()->user()->socialAccounts()->where('platform', $platform)->delete();

        return back()->with('status', '已解除連結。');
    }
}
