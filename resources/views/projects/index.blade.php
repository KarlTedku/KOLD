@extends('layouts.app')

@section('title', '合作項目 — KOLD')

@section('content')
<section class="section project-page">
    <div class="page-heading">
        <div>
            <span class="eyebrow">Brand Projects</span>
            <h2>搵適合你嘅合作</h2>
            <p class="lead">公開瀏覽合作內容；申請前需要登入 KOL 帳戶。</p>
        </div>
        @auth
            @if (auth()->user()->isBrand())
                <a class="btn btn-primary" href="{{ route('projects.create') }}">發佈新項目</a>
            @endif
        @endauth
    </div>

    <div class="project-list">
        @forelse ($projects as $project)
            <article class="project-row">
                <div class="project-main">
                    <div class="project-brand">{{ $project->brand->profileDisplayName() }}</div>
                    <h3><a href="{{ route('projects.show', $project) }}">{{ $project->title }}</a></h3>
                    <p>{{ \Illuminate\Support\Str::limit($project->brief, 180) }}</p>
                    <div class="meta">
                        @foreach ($project->niches ?? [] as $niche)<span class="chip">{{ $niche }}</span>@endforeach
                        @foreach ($project->regions ?? [] as $region)<span class="chip">{{ $region }}</span>@endforeach
                        @foreach ($project->platforms ?? [] as $platform)<span class="chip chip-accent">{{ ucfirst($platform) }}</span>@endforeach
                    </div>
                </div>
                <div class="project-facts">
                    <span>預算</span>
                    <strong>
                        @if ($project->budget_min || $project->budget_max)
                            HK$ {{ number_format((int) ($project->budget_min ?? 0)) }} - {{ number_format((int) ($project->budget_max ?? 0)) }}
                        @else
                            面議
                        @endif
                    </strong>
                    <span>申請截止</span>
                    <strong>{{ $project->application_deadline?->format('Y-m-d') ?? '未指定' }}</strong>
                    <a class="btn btn-soft" href="{{ route('projects.show', $project) }}">查看詳情</a>
                </div>
            </article>
        @empty
            <div class="empty-state">暫時未有公開合作項目。</div>
        @endforelse
    </div>

    {{ $projects->links() }}
</section>
@endsection
