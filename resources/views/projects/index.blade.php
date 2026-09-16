@extends('layouts.app')

@section('title', '合作項目 — KOLD')

@section('content')
@php
    $budgetOptions = [
        3000 => 'HK$3,000',
        5000 => 'HK$5,000',
        10000 => 'HK$10,000',
        20000 => 'HK$20,000',
    ];
    $activeFilters = array_filter([
        $q !== '' ? '關鍵字：'.$q : null,
        $niche !== '' ? $options['niches'][$niche] : null,
        $region !== '' ? $options['regions'][$region] : null,
        $platform !== '' ? $options['platforms'][$platform] : null,
        $budgetMin > 0 ? '預算至少 '.$budgetOptions[$budgetMin] : null,
        $onlyOpen ? '只顯示可申請' : null,
    ]);
    $hasActiveQuery = count($activeFilters) > 0 || $sort !== 'latest';
@endphp

<section class="project-marketplace">
    <header class="project-market-hero">
        <div class="project-hero-copy">
            <span class="eyebrow">Brand Projects</span>
            <h1>搵到值得你<br>投入嘅合作</h1>
            <p>按內容方向、平台、地區同預算快速配對。公開瀏覽項目，登入 KOL 帳戶後即可申請。</p>
            @auth
                @if (auth()->user()->isBrand())
                    <a class="btn btn-accent" href="{{ route('projects.create') }}">發佈新項目</a>
                @endif
            @endauth
        </div>
        <div class="project-hero-stat" aria-label="目前合作項目數量">
            <span>{{ count($activeFilters) > 0 ? '符合條件' : '現有合作' }}</span>
            <strong>{{ number_format($projects->total()) }}</strong>
            <p>個公開項目等你發掘</p>
        </div>
    </header>

    <div class="project-market-layout">
        <aside class="project-filter-panel" aria-labelledby="project-filter-title">
            <form method="GET" action="{{ route('projects.index') }}">
                <div class="project-filter-heading">
                    <div>
                        <span class="filter-kicker">Filter</span>
                        <h2 id="project-filter-title">收窄結果</h2>
                    </div>
                    @if ($hasActiveQuery)
                        <a href="{{ route('projects.index') }}">清除</a>
                    @endif
                </div>

                <label for="project-search">搜尋合作</label>
                <input id="project-search" type="search" name="q" value="{{ $q }}" placeholder="項目名稱或內容關鍵字">

                <label for="project-niche">內容方向</label>
                <select id="project-niche" name="niche">
                    <option value="">全部方向</option>
                    @foreach ($options['niches'] as $value => $label)
                        <option value="{{ $value }}" @selected($niche === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <label for="project-platform">合作平台</label>
                <select id="project-platform" name="platform">
                    <option value="">全部平台</option>
                    @foreach ($options['platforms'] as $value => $label)
                        <option value="{{ $value }}" @selected($platform === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <label for="project-region">地區</label>
                <select id="project-region" name="region">
                    <option value="">全部地區</option>
                    @foreach ($options['regions'] as $value => $label)
                        <option value="{{ $value }}" @selected($region === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <label for="project-budget">可接受最低預算</label>
                <select id="project-budget" name="budget_min">
                    <option value="0">任何預算</option>
                    @foreach ($budgetOptions as $value => $label)
                        <option value="{{ $value }}" @selected($budgetMin === $value)>{{ $label }} 或以上</option>
                    @endforeach
                </select>

                <label for="project-sort">排列方式</label>
                <select id="project-sort" name="sort">
                    <option value="latest" @selected($sort === 'latest')>最新發佈</option>
                    <option value="deadline" @selected($sort === 'deadline')>最快截止</option>
                    <option value="budget" @selected($sort === 'budget')>預算由高至低</option>
                </select>

                <label class="project-filter-check">
                    <input type="checkbox" name="open" value="1" @checked($onlyOpen)>
                    <span>
                        <strong>只顯示可申請項目</strong>
                        <small>隱藏已過截止日期嘅合作</small>
                    </span>
                </label>

                <button class="btn btn-primary project-filter-submit" type="submit">套用篩選</button>
            </form>
        </aside>

        <div class="project-results">
            <div class="project-results-heading">
                <div aria-live="polite">
                    <span class="results-kicker">Opportunities</span>
                    <h2>{{ number_format($projects->total()) }} 個合作機會</h2>
                </div>
                <p>第 {{ $projects->currentPage() }} / {{ max(1, $projects->lastPage()) }} 頁</p>
            </div>

            @if (count($activeFilters) > 0)
                <div class="project-active-filters" aria-label="已套用篩選">
                    @foreach ($activeFilters as $activeFilter)
                        <span>{{ $activeFilter }}</span>
                    @endforeach
                </div>
            @endif

            <div class="project-list">
                @forelse ($projects as $project)
                    @php
                        $isOpen = $project->isOpen();
                        $objective = $project->campaign_objective
                            ? ($options['campaign_objectives'][$project->campaign_objective] ?? null)
                            : null;
                        $compensation = $project->compensation_type
                            ? ($options['compensation_types'][$project->compensation_type] ?? null)
                            : null;
                        $budget = match (true) {
                            (bool) ($project->budget_min && $project->budget_max) => 'HK$'.number_format($project->budget_min).'–'.number_format($project->budget_max),
                            (bool) $project->budget_min => 'HK$'.number_format($project->budget_min).' 起',
                            (bool) $project->budget_max => 'HK$'.number_format($project->budget_max).' 內',
                            default => '面議',
                        };
                    @endphp
                    <article class="project-row">
                        <div class="project-card-topline">
                            <span class="project-brand">
                                @if ($project->is_sample)
                                    KOLD 精選合作
                                @else
                                    {{ $project->brand->profileDisplayName() }}
                                @endif
                            </span>
                            <span class="project-status {{ $isOpen ? '' : 'is-closed' }}">
                                {{ $isOpen ? '現正招募' : '申請已截止' }}
                            </span>
                        </div>

                        <div class="project-main">
                            <h3><a href="{{ route('projects.show', $project) }}">{{ $project->title }}</a></h3>
                            <p>{{ \Illuminate\Support\Str::limit($project->brief, 150) }}</p>
                            <div class="project-card-tags">
                                @if ($objective)<span class="chip chip-objective">{{ $objective }}</span>@endif
                                @foreach (array_slice($project->niches ?? [], 0, 2) as $item)<span class="chip">{{ $item }}</span>@endforeach
                                @foreach (array_slice($project->regions ?? [], 0, 1) as $item)<span class="chip">{{ $item }}</span>@endforeach
                                @foreach ($project->platforms ?? [] as $item)<span class="chip chip-accent">{{ $options['platforms'][$item] ?? ucfirst($item) }}</span>@endforeach
                            </div>
                        </div>

                        <div class="project-card-footer">
                            <dl class="project-card-facts">
                                <div>
                                    <dt>合作預算</dt>
                                    <dd>{{ $budget }}</dd>
                                </div>
                                <div>
                                    <dt>申請截止</dt>
                                    <dd>
                                        @if ($project->application_deadline)
                                            <time datetime="{{ $project->application_deadline->format('Y-m-d') }}">{{ $project->application_deadline->format('Y.m.d') }}</time>
                                        @else
                                            未指定
                                        @endif
                                    </dd>
                                </div>
                                @if ($compensation)
                                    <div>
                                        <dt>報酬方式</dt>
                                        <dd>{{ $compensation }}</dd>
                                    </div>
                                @endif
                            </dl>
                            <a class="btn btn-soft" href="{{ route('projects.show', $project) }}">查看合作詳情</a>
                        </div>
                    </article>
                @empty
                    <div class="project-empty-state">
                        <span>未搵到合適項目</span>
                        <h3>試下放寬其中一個條件</h3>
                        <p>你可以清除篩選，再睇返所有公開合作機會。</p>
                        <a class="btn btn-primary" href="{{ route('projects.index') }}">查看全部項目</a>
                    </div>
                @endforelse
            </div>

            @if ($projects->hasPages())
                <nav class="project-pagination" aria-label="合作項目分頁">
                    @if ($projects->onFirstPage())
                        <span class="btn btn-ghost is-disabled" aria-disabled="true">上一頁</span>
                    @else
                        <a class="btn btn-ghost" href="{{ $projects->previousPageUrl() }}" rel="prev">上一頁</a>
                    @endif
                    <span>第 {{ $projects->currentPage() }} 頁</span>
                    @if ($projects->hasMorePages())
                        <a class="btn btn-ghost" href="{{ $projects->nextPageUrl() }}" rel="next">下一頁</a>
                    @else
                        <span class="btn btn-ghost is-disabled" aria-disabled="true">下一頁</span>
                    @endif
                </nav>
            @endif
        </div>
    </div>
</section>
@endsection
