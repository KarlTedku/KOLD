<?php

namespace App\Http\Controllers;

use App\Models\KolCardLink;
use App\Models\KolProfile;
use App\Models\KolProfileSlugAlias;
use App\Services\KolSlugService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KolCardController extends Controller
{
    public function show(string $slug): View|RedirectResponse
    {
        $relations = [
            'user.socialAccounts',
            'approvedAiTags',
            'cardLinks' => fn ($query) => $query->where('is_active', true),
        ];

        $profile = KolProfile::query()
            ->with($relations)
            ->where('slug', $slug)
            ->where('status', 'published')
            ->first();

        if (! $profile) {
            $alias = KolProfileSlugAlias::query()
                ->with(['kolProfile' => fn ($query) => $query->with($relations)])
                ->where('slug', $slug)
                ->first();
            $profile = $alias?->kolProfile;

            if (! $profile || ! $profile->isPublished() || blank($profile->slug)) {
                abort(404);
            }

            return redirect()->route('kol-card.show', $profile->slug, 301);
        }

        return view('kol-card.show', ['profile' => $profile, 'isPreview' => false]);
    }

    public function preview(Request $request): View
    {
        $profile = $this->currentKolProfile($request);
        $profile->load([
            'user.socialAccounts',
            'approvedAiTags',
            'cardLinks' => fn ($query) => $query->where('is_active', true),
        ]);

        return view('kol-card.show', [
            'profile' => $profile,
            'isPreview' => true,
            'isEmbedded' => $request->boolean('embedded'),
        ]);
    }

    public function update(Request $request, KolSlugService $slugs): RedirectResponse
    {
        abort_unless($request->user()->isKol(), 403);

        $profile = $request->user()->kolProfile()->firstOrCreate(
            ['user_id' => $request->user()->id],
            ['display_name' => $request->user()->name, 'status' => 'draft']
        );

        $requestedSlug = $slugs->normalize((string) $request->input('slug'));
        $request->merge(['slug' => $requestedSlug]);

        if ($profile->isSlugLocked() && $requestedSlug !== $profile->slug) {
            return back()
                ->withErrors(['slug' => '公開網址首次發布後已鎖定。如有必要更改，請聯絡支援。'])
                ->withInput();
        }

        $data = $request->validate([
            'slug' => $slugs->validationRules($profile),
            'card_headline' => ['nullable', 'string', 'max:160'],
            'external_contact_url' => ['nullable', 'url', 'max:500'],
            'card_theme' => ['nullable', Rule::in(array_keys(config('kold.card_themes')))],
            'card_accent' => ['nullable', Rule::in(array_keys(config('kold.card_accents')))],
            'background' => $this->backgroundRules(),
        ]);

        $profile->update([
            'slug' => $data['slug'],
            'card_headline' => $data['card_headline'] ?? null,
            'external_contact_url' => $data['external_contact_url'] ?? null,
            'card_theme' => $data['card_theme'] ?? $profile->card_theme ?? 'classic',
            'card_accent' => $data['card_accent'] ?? $profile->card_accent ?? 'moss',
        ]);

        if (isset($data['background'])) {
            $this->replaceBackground($profile, $data['background']);
        }

        return redirect()->route('profile.edit', ['step' => 'links'])
            ->with('status', '卡片網址及介紹已儲存。');
    }

    public function destroyBackground(Request $request): RedirectResponse
    {
        $profile = $this->currentKolProfile($request);
        $path = $profile->card_background_path;
        $profile->update(['card_background_path' => null]);

        if ($path && str_starts_with($path, "card-backgrounds/{$profile->id}/")) {
            Storage::disk('public')->delete($path);
        }

        return redirect()->route('profile.edit', ['step' => 'card'])
            ->with('status', '已移除卡片背景。');
    }

    /** @return array<int, string> */
    private function backgroundRules(): array
    {
        return [
            'nullable',
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:6144',
            'dimensions:min_width=600,min_height=600,max_width=6000,max_height=6000',
        ];
    }

    private function replaceBackground(KolProfile $profile, UploadedFile $image): void
    {
        $oldPath = $profile->card_background_path;
        $path = $image->store("card-backgrounds/{$profile->id}", 'public');
        $profile->update(['card_background_path' => $path]);

        if ($oldPath && str_starts_with($oldPath, "card-backgrounds/{$profile->id}/")) {
            Storage::disk('public')->delete($oldPath);
        }
    }

    public function storeLink(Request $request): RedirectResponse
    {
        $profile = $this->currentKolProfile($request);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:80'],
            'url' => ['required', 'url', 'max:500'],
            'type' => ['nullable', 'string', 'max:40'],
            'icon' => ['nullable', Rule::in(array_keys(config('kold.card_link_icons')))],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $profile->cardLinks()->create([
            'title' => $data['title'],
            'url' => $data['url'],
            'type' => $data['type'] ?? 'custom',
            'icon' => $data['icon'] ?? 'link',
            'sort_order' => $data['sort_order'] ?? ($profile->cardLinks()->max('sort_order') + 10),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return $this->redirectToStep('links', '卡片連結已新增。');
    }

    public function storeSocialLinks(Request $request): RedirectResponse
    {
        $profile = $this->currentKolProfile($request);
        $profile->loadMissing('user.socialAccounts', 'cardLinks');

        $created = 0;
        $sortOrder = (int) $profile->cardLinks()->max('sort_order');

        foreach ($profile->user->socialAccounts as $account) {
            $url = $this->socialUrl((string) $account->platform, (string) $account->handle);
            if (! $url || $profile->cardLinks()->where('url', $url)->exists()) {
                continue;
            }

            $sortOrder += 10;
            $profile->cardLinks()->create([
                'title' => ucfirst((string) $account->platform),
                'url' => $url,
                'type' => (string) $account->platform,
                'icon' => array_key_exists((string) $account->platform, config('kold.card_link_icons'))
                    ? (string) $account->platform
                    : 'link',
                'sort_order' => $sortOrder,
                'is_active' => true,
            ]);
            $created++;
        }

        return $this->redirectToStep('links', $created > 0 ? "已加入 {$created} 個社群連結。" : '未有新的社群連結可加入。');
    }

    public function updateLink(Request $request, KolCardLink $kolCardLink): RedirectResponse
    {
        $this->authorizeLink($request, $kolCardLink);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:80'],
            'url' => ['required', 'url', 'max:500'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'icon' => ['nullable', Rule::in(array_keys(config('kold.card_link_icons')))],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $kolCardLink->update([
            'title' => $data['title'],
            'url' => $data['url'],
            'sort_order' => $data['sort_order'],
            'icon' => $data['icon'] ?? $kolCardLink->icon ?? 'link',
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return $this->redirectToStep('links', '卡片連結已更新。');
    }

    public function destroyLink(Request $request, KolCardLink $kolCardLink): RedirectResponse
    {
        $this->authorizeLink($request, $kolCardLink);
        $kolCardLink->delete();

        return $this->redirectToStep('links', '卡片連結已刪除。');
    }

    public function publish(Request $request): RedirectResponse
    {
        $profile = $this->currentKolProfile($request);
        $issues = $profile->cardPublicationIssues();

        if ($issues !== []) {
            return redirect()->route('profile.edit', ['step' => 'preview'])
                ->withErrors(['publish' => implode(' ', $issues)]);
        }

        $wasLocked = $profile->isSlugLocked();
        if (! $wasLocked) {
            $request->validate([
                'confirm_slug_lock' => ['accepted'],
            ], [
                'confirm_slug_lock.accepted' => '請先確認公開網址首次發布後會鎖定。',
            ]);
        }

        $profile->update([
            'status' => 'published',
            'slug_locked_at' => $profile->slug_locked_at ?? now(),
        ]);

        return redirect()->route('profile.edit', ['step' => 'preview'])
            ->with('status', $wasLocked
                ? 'KOL 卡片已發布，可以分享公開網址。'
                : 'KOL 卡片已發布，公開網址亦已鎖定。');
    }

    public function unpublish(Request $request): RedirectResponse
    {
        $profile = $this->currentKolProfile($request);
        $profile->update([
            'status' => 'draft',
            'slug_locked_at' => $profile->slug_locked_at ?? now(),
        ]);

        return redirect()->route('profile.edit', ['step' => 'preview'])
            ->with('status', 'KOL 卡片已轉為草稿，公開網址暫時不會顯示。');
    }

    protected function currentKolProfile(Request $request): KolProfile
    {
        abort_unless($request->user()->isKol(), 403);

        return $request->user()->kolProfile()->firstOrCreate(
            ['user_id' => $request->user()->id],
            ['display_name' => $request->user()->name, 'status' => 'draft']
        );
    }

    protected function authorizeLink(Request $request, KolCardLink $kolCardLink): void
    {
        abort_unless($request->user()->isKol(), 403);
        abort_unless($kolCardLink->kolProfile?->user_id === $request->user()->id, 403);
    }

    protected function socialUrl(string $platform, string $handle): ?string
    {
        $handle = ltrim(trim($handle), '@');
        if ($handle === '') {
            return null;
        }

        if (Str::startsWith($handle, ['http://', 'https://'])) {
            return $handle;
        }

        return match ($platform) {
            'instagram' => 'https://instagram.com/'.$handle,
            'facebook' => 'https://facebook.com/'.$handle,
            'youtube' => 'https://www.youtube.com/@'.$handle,
            default => null,
        };
    }

    protected function redirectToStep(string $step, string $status): RedirectResponse
    {
        return redirect()->route('profile.edit', ['step' => $step])->with('status', $status);
    }
}
