<?php

namespace App\Http\Controllers;

use App\Models\SocialAccount;
use App\Services\SocialSyncService;
use App\Services\UserAvatarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;

class SocialAccountController extends Controller
{
    public function index(): View
    {
        $accounts = auth()->user()
            ->socialAccounts()
            ->orderBy('platform')
            ->orderBy('account_type')
            ->orderByDesc('is_primary')
            ->get()
            ->groupBy(fn (SocialAccount $account) => $account->account_type ?: 'manual');

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

        if (! filled(config('services.facebook_connect.client_id'))
            || ! filled(config('services.facebook_connect.client_secret'))) {
            return back()->with('error', '尚未設定 Meta OAuth，請先用手動填寫。');
        }

        session()->put('social_connect', [
            'provider' => 'facebook',
            'started_at' => now()->timestamp,
        ]);
        session()->forget('meta_connect_candidates');

        config(['services.facebook' => config('services.facebook_connect')]);

        return Socialite::driver('facebook')
            ->setScopes(['pages_show_list', 'instagram_basic'])
            ->redirect();
    }

    public function selectMetaAccounts(Request $request): View|RedirectResponse
    {
        $candidates = collect($request->session()->get('meta_connect_candidates', []))
            ->filter(fn (array $candidate) => is_array($candidate['instagram'] ?? null))
            ->values();

        if ($candidates->isEmpty()) {
            return redirect()->route('social.index')->with('error', '沒有可連結的 Instagram Business／Creator 帳號。');
        }

        return view('social.meta-select', ['candidates' => $candidates]);
    }

    public function storeMetaAccounts(
        Request $request,
        SocialSyncService $sync,
        UserAvatarService $avatars
    ): RedirectResponse {
        $data = $request->validate([
            'accounts' => ['required', 'array', 'min:1'],
            'accounts.*' => ['required', 'string'],
            'primary' => ['nullable', 'string'],
        ]);

        $candidates = collect($request->session()->get('meta_connect_candidates', []))
            ->keyBy('key');

        $selected = collect($data['accounts'])
            ->map(fn (string $key) => $candidates->get($key))
            ->filter(fn ($candidate) => is_array($candidate))
            ->values();

        if ($selected->isEmpty()) {
            return back()->with('error', '請選擇至少一個可連結的 Instagram 帳號。');
        }

        $primaryKey = $data['primary'] ?? data_get($selected->first(), 'key');
        if (! $selected->contains(fn (array $candidate) => ($candidate['key'] ?? null) === $primaryKey)) {
            $primaryKey = data_get($selected->first(), 'key');
        }

        $request->user()
            ->socialAccounts()
            ->where('account_type', 'instagram_business')
            ->update(['is_primary' => false]);

        $selected->each(function (array $candidate) use ($request, $sync, $primaryKey): void {
            $sync->syncFacebookPageCandidate(
                $request->user(),
                $candidate,
                ($candidate['key'] ?? null) === $primaryKey
            );
        });

        $primaryCandidate = $selected->first(
            fn (array $candidate) => ($candidate['key'] ?? null) === $primaryKey
        );
        $avatars->setFromMeta(
            $request->user(),
            data_get($primaryCandidate, 'instagram.profile_picture_url')
        );

        $request->session()->forget(['social_connect', 'meta_connect_candidates']);

        return redirect()->route('social.index')->with('status', 'Meta／Instagram 帳號已連結。');
    }

    public function destroy(SocialAccount $socialAccount): RedirectResponse
    {
        abort_unless($socialAccount->user_id === auth()->id(), 403);

        $socialAccount->delete();

        return back()->with('status', '已解除連結。');
    }
}
