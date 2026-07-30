<?php

namespace App\Http\Controllers;

use App\Services\ProfileAiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        $user = auth()->user()->load(['kolProfile', 'brandProfile', 'socialAccounts']);

        return view('profile.edit', compact('user'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->isKol()) {
            $data = $request->validate([
                'display_name' => ['required', 'string', 'max:120'],
                'bio' => ['nullable', 'string', 'max:2000'],
                'niches' => ['nullable', 'string', 'max:500'],
                'regions' => ['nullable', 'string', 'max:500'],
                'languages' => ['nullable', 'string', 'max:500'],
                'age_range' => ['nullable', 'in:18-24,25-34,35-44'],
                'photos' => ['nullable', 'string', 'max:4000'],
                'rate_min' => ['nullable', 'integer', 'min:0'],
                'rate_max' => ['nullable', 'integer', 'min:0'],
                'status' => ['required', 'in:draft,published'],
            ]);

            $user->kolProfile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'display_name' => $data['display_name'],
                    'bio' => $data['bio'] ?? null,
                    'niches' => $this->splitList($data['niches'] ?? null),
                    'regions' => $this->splitList($data['regions'] ?? null),
                    'languages' => $this->splitList($data['languages'] ?? null),
                    'age_range' => $data['age_range'] ?? null,
                    'photos' => $this->splitList($data['photos'] ?? null),
                    'rate_min' => $data['rate_min'] ?? null,
                    'rate_max' => $data['rate_max'] ?? null,
                    'status' => $data['status'],
                ]
            );
        } else {
            $data = $request->validate([
                'company_name' => ['required', 'string', 'max:120'],
                'bio' => ['nullable', 'string', 'max:2000'],
                'industries' => ['nullable', 'string', 'max:500'],
                'regions' => ['nullable', 'string', 'max:500'],
                'budget_range' => ['nullable', 'string', 'max:120'],
                'status' => ['required', 'in:draft,published'],
            ]);

            $user->brandProfile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'company_name' => $data['company_name'],
                    'bio' => $data['bio'] ?? null,
                    'industries' => $this->splitList($data['industries'] ?? null),
                    'regions' => $this->splitList($data['regions'] ?? null),
                    'budget_range' => $data['budget_range'] ?? null,
                    'status' => $data['status'],
                ]
            );
        }

        return back()->with('status', '檔案已儲存。');
    }

    public function aiDraft(Request $request, ProfileAiService $ai): RedirectResponse
    {
        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user()->load(['kolProfile', 'brandProfile', 'socialAccounts']);
        $draft = $ai->generateDraft($user, $data['notes'] ?? null);

        if ($user->isKol()) {
            $profile = $user->kolProfile()->firstOrCreate(
                ['user_id' => $user->id],
                ['display_name' => $user->name, 'status' => 'draft']
            );

            $profile->update([
                'bio' => $draft['bio'],
                'niches' => $draft['niches'],
            ]);
        } else {
            $profile = $user->brandProfile()->firstOrCreate(
                ['user_id' => $user->id],
                ['company_name' => $user->name, 'status' => 'draft']
            );

            $profile->update([
                'bio' => $draft['bio'],
                'industries' => $draft['niches'],
            ]);
        }

        return back()->with('status', 'AI 已產生檔案草稿，請檢視後再發佈。')->with('ai_draft', $draft);
    }

    /**
     * @return array<int, string>
     */
    protected function splitList(?string $value): array
    {
        if (! filled($value)) {
            return [];
        }

        return collect(preg_split('/[,，、]+/u', $value) ?: [])
            ->map(fn ($item) => trim($item))
            ->filter()
            ->values()
            ->all();
    }
}
