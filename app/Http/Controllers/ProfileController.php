<?php

namespace App\Http\Controllers;

use App\Services\ProfileAiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = auth()->user()->load([
            'kolProfile.cardLinks',
            'kolProfile.aiTags',
            'brandProfile',
            'socialAccounts',
        ]);

        $steps = ['profile', 'card', 'links', 'tags', 'preview'];
        $step = in_array($request->query('step'), $steps, true)
            ? $request->query('step')
            : 'profile';

        $progress = [];
        if ($user->isKol()) {
            $profile = $user->kolProfile;
            $progress = [
                'profile' => filled($profile?->display_name) && filled($profile?->bio),
                'card' => filled($profile?->slug) && filled($profile?->card_headline),
                'links' => (bool) $profile?->cardLinks->contains('is_active', true),
                'tags' => (bool) $profile?->aiTags->contains('status', 'approved'),
                'preview' => $profile?->status === 'published',
            ];
        }

        return view('profile.edit', compact('user', 'step', 'progress'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->isKol()) {
            $options = config('kold.profile_options');
            $data = $request->validate([
                'display_name' => ['required', 'string', 'max:120'],
                'bio' => ['nullable', 'string', 'max:2000'],
                'niches' => ['nullable', 'array', 'max:'.(count($options['niches']) + 1)],
                'niches.*' => ['string', 'distinct', Rule::in([...array_keys($options['niches']), 'other'])],
                'niches_other' => [
                    'nullable', 'string', 'max:120',
                    Rule::requiredIf(fn (): bool => in_array('other', (array) $request->input('niches', []), true)),
                ],
                'regions' => ['nullable', 'array', 'max:'.(count($options['regions']) + 1)],
                'regions.*' => ['string', 'distinct', Rule::in([...array_keys($options['regions']), 'other'])],
                'regions_other' => [
                    'nullable', 'string', 'max:120',
                    Rule::requiredIf(fn (): bool => in_array('other', (array) $request->input('regions', []), true)),
                ],
                'languages' => ['nullable', 'array', 'max:'.(count($options['languages']) + 1)],
                'languages.*' => ['string', 'distinct', Rule::in([...array_keys($options['languages']), 'other'])],
                'languages_other' => [
                    'nullable', 'string', 'max:120',
                    Rule::requiredIf(fn (): bool => in_array('other', (array) $request->input('languages', []), true)),
                ],
                'age_range' => ['nullable', Rule::in(array_keys($options['age_ranges']))],
                'photos' => ['nullable', 'string', 'max:4000'],
                'rate_range' => ['nullable', Rule::in([...array_keys($options['rate_ranges']), 'existing'])],
            ]);

            $rateMin = $user->kolProfile?->rate_min;
            $rateMax = $user->kolProfile?->rate_max;
            if (isset($data['rate_range']) && $data['rate_range'] === 'existing' && ! $user->kolProfile) {
                return back()->withErrors(['rate_range' => '請選擇一個報價範圍。'])->withInput();
            }
            if (isset($data['rate_range']) && $data['rate_range'] !== 'existing') {
                $rateMin = $options['rate_ranges'][$data['rate_range']]['min'];
                $rateMax = $options['rate_ranges'][$data['rate_range']]['max'];
            }

            $user->kolProfile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'display_name' => $data['display_name'],
                    'bio' => $data['bio'] ?? null,
                    'niches' => $this->mergeOtherSelection($data['niches'] ?? [], $data['niches_other'] ?? null),
                    'regions' => $this->mergeOtherSelection($data['regions'] ?? [], $data['regions_other'] ?? null),
                    'languages' => $this->mergeOtherSelection($data['languages'] ?? [], $data['languages_other'] ?? null),
                    'age_range' => $data['age_range'] ?? null,
                    'photos' => $this->splitList($data['photos'] ?? null),
                    'rate_min' => $rateMin,
                    'rate_max' => $rateMax,
                    'status' => $user->kolProfile?->status ?? 'draft',
                ]
            );

            return redirect()->route('profile.edit', ['step' => 'card'])
                ->with('status', '基本資料已儲存。');
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

    /**
     * @param  array<int, string>  $selected
     * @return array<int, string>
     */
    protected function mergeOtherSelection(array $selected, ?string $other): array
    {
        $includesOther = in_array('other', $selected, true);

        return collect($selected)
            ->reject(fn (string $value): bool => $value === 'other')
            ->when(
                $includesOther,
                fn ($values) => $values->merge(preg_split('/[,，、]+/u', (string) $other) ?: [])
            )
            ->map(fn (string $value): string => trim($value))
            ->filter()
            ->unique(fn (string $value): string => mb_strtolower($value))
            ->values()
            ->all();
    }
}
