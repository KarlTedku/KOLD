<?php

namespace App\Http\Controllers;

use App\Models\BrandProject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BrandProjectController extends Controller
{
    public function index(Request $request): View
    {
        $options = config('kold.project_options');
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 80);
        $niche = trim((string) $request->query('niche', ''));
        $region = trim((string) $request->query('region', ''));
        $platform = trim((string) $request->query('platform', ''));
        $budgetMin = (int) $request->query('budget_min', 0);
        $onlyOpen = $request->boolean('open');
        $sort = trim((string) $request->query('sort', 'latest'));

        $niche = array_key_exists($niche, $options['niches']) ? $niche : '';
        $region = array_key_exists($region, $options['regions']) ? $region : '';
        $platform = array_key_exists($platform, $options['platforms']) ? $platform : '';
        $budgetMin = in_array($budgetMin, [3000, 5000, 10000, 20000], true) ? $budgetMin : 0;
        $sort = in_array($sort, ['latest', 'deadline', 'budget'], true) ? $sort : 'latest';

        $query = BrandProject::query()
            ->with('brand.brandProfile')
            ->where('status', 'published')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('title', 'like', "%{$q}%")
                        ->orWhere('brief', 'like', "%{$q}%");
                });
            })
            ->when($niche !== '', fn ($query) => $query->whereJsonContains('niches', $niche))
            ->when($region !== '', fn ($query) => $query->whereJsonContains('regions', $region))
            ->when($platform !== '', fn ($query) => $query->whereJsonContains('platforms', $platform))
            ->when($budgetMin > 0, function ($query) use ($budgetMin) {
                $query->where(function ($budget) use ($budgetMin) {
                    $budget->where('budget_max', '>=', $budgetMin)
                        ->orWhere(function ($openEnded) use ($budgetMin) {
                            $openEnded->whereNull('budget_max')
                                ->where('budget_min', '>=', $budgetMin);
                        });
                });
            })
            ->when($onlyOpen, function ($query) {
                $query->where(function ($deadline) {
                    $deadline->whereNull('application_deadline')
                        ->orWhereDate('application_deadline', '>=', today());
                });
            });

        match ($sort) {
            'deadline' => $query
                ->orderByRaw('CASE WHEN application_deadline IS NULL THEN 1 ELSE 0 END')
                ->orderBy('application_deadline')
                ->latest('id'),
            'budget' => $query
                ->orderByRaw('COALESCE(budget_max, budget_min, 0) DESC')
                ->latest('id'),
            default => $query->latest(),
        };

        $projects = $query->paginate(12)->withQueryString();

        return view('projects.index', compact(
            'projects',
            'options',
            'q',
            'niche',
            'region',
            'platform',
            'budgetMin',
            'onlyOpen',
            'sort',
        ));
    }

    public function show(Request $request, BrandProject $project): View
    {
        abort_unless($project->status === 'published' || $request->user()?->id === $project->brand_user_id, 404);

        $project->load('brand.brandProfile');
        $application = $request->user()?->isKol()
            ? $project->applications()
                ->with('collaboration.conversation')
                ->where('kol_user_id', $request->user()->id)
                ->first()
            : null;

        return view('projects.show', compact('project', 'application'));
    }

    public function manage(Request $request): View
    {
        $this->authorizeBrand($request);
        $projects = $request->user()->brandProjects()->withCount('applications')->latest()->get();

        return view('projects.manage', compact('projects'));
    }

    public function create(Request $request): View
    {
        $this->authorizeBrand($request);

        return view('projects.form', ['project' => new BrandProject]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeBrand($request);
        $data = $this->validated($request);
        $data['brand_user_id'] = $request->user()->id;
        $data['slug'] = $this->uniqueSlug($data['title']);
        $data['status'] = 'draft';
        $project = BrandProject::query()->create($data);

        return redirect()->route('projects.edit', $project)->with('status', 'Project 草稿已建立。');
    }

    public function edit(Request $request, BrandProject $project): View
    {
        $this->authorizeOwner($request, $project);

        return view('projects.form', compact('project'));
    }

    public function update(Request $request, BrandProject $project): RedirectResponse
    {
        $this->authorizeOwner($request, $project);
        $project->update($this->validated($request, $project));

        return back()->with('status', 'Project 已儲存。');
    }

    public function publish(Request $request, BrandProject $project): RedirectResponse
    {
        $this->authorizeOwner($request, $project);
        $project->update(['status' => 'published']);

        return redirect()->route('projects.show', $project)->with('status', 'Project 已公開，KOL 可以申請。');
    }

    public function close(Request $request, BrandProject $project): RedirectResponse
    {
        $this->authorizeOwner($request, $project);
        $project->update(['status' => 'closed']);

        return back()->with('status', 'Project 已截止申請。');
    }

    /** @return array<string, mixed> */
    protected function validated(Request $request, ?BrandProject $project = null): array
    {
        $options = config('kold.project_options');

        $data = $request->validate([
            'title' => ['required', 'string', 'max:140'],
            'campaign_objective' => ['nullable', Rule::in(array_keys($options['campaign_objectives']))],
            'brief' => ['required', 'string', 'min:20', 'max:5000'],
            'niches' => ['nullable', 'array', 'max:11'],
            'niches.*' => ['string', 'distinct', Rule::in([...array_keys($options['niches']), 'other'])],
            'niches_other' => [
                'nullable', 'string', 'max:120',
                Rule::requiredIf(fn (): bool => in_array('other', (array) $request->input('niches', []), true)),
            ],
            'regions' => ['nullable', 'array', 'max:6'],
            'regions.*' => ['string', 'distinct', Rule::in([...array_keys($options['regions']), 'other'])],
            'regions_other' => [
                'nullable', 'string', 'max:120',
                Rule::requiredIf(fn (): bool => in_array('other', (array) $request->input('regions', []), true)),
            ],
            'platforms' => ['nullable', 'array', 'max:3'],
            'platforms.*' => ['string', 'distinct', Rule::in(array_keys($options['platforms']))],
            'target_audience' => ['nullable', 'string', 'max:500'],
            'collaboration_formats' => ['nullable', 'array', 'max:7'],
            'collaboration_formats.*' => ['string', 'distinct', Rule::in(array_keys($options['collaboration_formats']))],
            'compensation_type' => ['nullable', Rule::in(array_keys($options['compensation_types']))],
            'budget_min' => ['nullable', 'integer', 'min:0'],
            'budget_max' => ['nullable', 'integer', 'min:0', 'gte:budget_min'],
            'deliverables' => ['nullable', 'string', 'max:3000'],
            'usage_rights' => ['nullable', Rule::in(array_keys($options['usage_rights']))],
            'application_deadline' => ['nullable', 'date'],
            'campaign_start_date' => ['nullable', 'date'],
            'campaign_end_date' => ['nullable', 'date', 'after_or_equal:campaign_start_date'],
        ]);

        $data['niches'] = $this->mergeOtherSelection($data['niches'] ?? [], $data['niches_other'] ?? null);
        $data['regions'] = $this->mergeOtherSelection($data['regions'] ?? [], $data['regions_other'] ?? null);
        $data['platforms'] = array_values($data['platforms'] ?? []);
        $data['collaboration_formats'] = array_values($data['collaboration_formats'] ?? []);
        unset($data['niches_other'], $data['regions_other']);

        return $data;
    }

    /** @return array<int, string> */
    protected function splitList(string $value): array
    {
        return array_values(array_unique(array_filter(array_map('trim', preg_split('/[,，、\r\n]+/u', $value) ?: []))));
    }

    /** @param array<int, string> $selected
     * @return array<int, string>
     */
    protected function mergeOtherSelection(array $selected, ?string $other): array
    {
        $includesOther = in_array('other', $selected, true);
        $selected = array_values(array_filter($selected, fn (string $value): bool => $value !== 'other'));

        if (! $includesOther) {
            return $selected;
        }

        return array_values(array_unique([...$selected, ...$this->splitList($other ?? '')]));
    }

    protected function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'project';
        $slug = $base;
        $counter = 2;
        while (BrandProject::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }

    protected function authorizeBrand(Request $request): void
    {
        abort_unless($request->user()->isBrand(), 403);
    }

    protected function authorizeOwner(Request $request, BrandProject $project): void
    {
        $this->authorizeBrand($request);
        abort_unless($project->brand_user_id === $request->user()->id, 403);
    }
}
