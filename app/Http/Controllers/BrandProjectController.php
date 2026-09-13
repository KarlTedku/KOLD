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
    public function index(): View
    {
        $projects = BrandProject::query()
            ->with('brand.brandProfile')
            ->where('status', 'published')
            ->latest()
            ->paginate(12);

        return view('projects.index', compact('projects'));
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
        $data = $request->validate([
            'title' => ['required', 'string', 'max:140'],
            'brief' => ['required', 'string', 'min:20', 'max:5000'],
            'niches' => ['nullable', 'string', 'max:500'],
            'regions' => ['nullable', 'string', 'max:500'],
            'platforms' => ['nullable', 'array'],
            'platforms.*' => [Rule::in(['instagram', 'facebook', 'youtube'])],
            'budget_min' => ['nullable', 'integer', 'min:0'],
            'budget_max' => ['nullable', 'integer', 'min:0', 'gte:budget_min'],
            'deliverables' => ['nullable', 'string', 'max:3000'],
            'application_deadline' => ['nullable', 'date'],
            'campaign_start_date' => ['nullable', 'date'],
            'campaign_end_date' => ['nullable', 'date', 'after_or_equal:campaign_start_date'],
        ]);

        $data['niches'] = $this->splitList($data['niches'] ?? '');
        $data['regions'] = $this->splitList($data['regions'] ?? '');
        $data['platforms'] = array_values($data['platforms'] ?? []);

        return $data;
    }

    /** @return array<int, string> */
    protected function splitList(string $value): array
    {
        return array_values(array_unique(array_filter(array_map('trim', preg_split('/[,，]/u', $value) ?: []))));
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
