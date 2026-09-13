@extends('layouts.app')

@section('title', $project->title.' — KOLD')

@section('content')
<section class="section project-page">
    <a class="back-link" href="{{ route('projects.index') }}">← 返回合作項目</a>
    <div class="project-detail">
        <article class="project-copy">
            <span class="eyebrow">{{ $project->brand->profileDisplayName() }}</span>
            <h1>{{ $project->title }}</h1>
            <div class="meta">
                @foreach ($project->niches ?? [] as $niche)<span class="chip">{{ $niche }}</span>@endforeach
                @foreach ($project->regions ?? [] as $region)<span class="chip">{{ $region }}</span>@endforeach
                @foreach ($project->platforms ?? [] as $platform)<span class="chip chip-accent">{{ ucfirst($platform) }}</span>@endforeach
            </div>

            <section>
                <h2>合作內容</h2>
                <div class="project-prose">{!! nl2br(e($project->brief)) !!}</div>
            </section>
            @if ($project->deliverables)
                <section>
                    <h2>交付要求</h2>
                    <div class="project-prose">{!! nl2br(e($project->deliverables)) !!}</div>
                </section>
            @endif
        </article>

        <aside class="project-apply">
            <dl class="project-summary">
                <div><dt>預算</dt><dd>@if ($project->budget_min || $project->budget_max)HK$ {{ number_format((int) ($project->budget_min ?? 0)) }} - {{ number_format((int) ($project->budget_max ?? 0)) }}@else 面議 @endif</dd></div>
                <div><dt>申請截止</dt><dd>{{ $project->application_deadline?->format('Y-m-d') ?? '未指定' }}</dd></div>
                <div><dt>合作日期</dt><dd>{{ $project->campaign_start_date?->format('Y-m-d') ?? '待定' }} 至 {{ $project->campaign_end_date?->format('Y-m-d') ?? '待定' }}</dd></div>
                <div><dt>狀態</dt><dd>{{ $project->isOpen() ? '接受申請中' : ($project->status === 'draft' ? '草稿' : '已截止') }}</dd></div>
            </dl>

            @auth
                @if (auth()->id() === $project->brand_user_id)
                    <a class="btn btn-primary" href="{{ route('projects.edit', $project) }}">編輯項目</a>
                    <a class="btn btn-soft" href="{{ route('projects.applications', $project) }}">查看申請</a>
                @elseif (auth()->user()->isKol())
                    @if ($application)
                        <div class="application-state">
                            <strong>你已提交申請</strong>
                            <span>狀態：{{ ['pending' => '待審核', 'accepted' => '已接受', 'declined' => '未獲選', 'withdrawn' => '已撤回'][$application->status] ?? $application->status }}</span>
                            @if ($application->status === 'pending')
                                <form method="POST" action="{{ route('applications.withdraw', $application) }}">@csrf @method('DELETE')<button class="btn btn-ghost" type="submit">撤回申請</button></form>
                            @elseif ($application->status === 'accepted' && $application->collaboration?->conversation)
                                <a class="btn btn-primary" href="{{ route('conversations.show', $application->collaboration->conversation) }}">進入洽談</a>
                            @endif
                        </div>
                    @elseif ($project->isOpen())
                        <h2>申請合作</h2>
                        <form method="POST" action="{{ route('projects.apply', $project) }}">
                            @csrf
                            <label for="pitch">合作構思</label>
                            <textarea id="pitch" name="pitch" required minlength="20" placeholder="介紹你嘅內容方向、受眾及合作構思">{{ old('pitch') }}</textarea>
                            <label for="proposed_rate">建議報價（HK$，可選）</label>
                            <input id="proposed_rate" name="proposed_rate" type="number" min="0" value="{{ old('proposed_rate') }}">
                            <button class="btn btn-accent" type="submit">提交申請</button>
                        </form>
                    @else
                        <p>此項目暫停接受申請。</p>
                    @endif
                @else
                    <p>請以 KOL 帳戶登入申請。</p>
                @endif
            @else
                <p>登入 KOL 帳戶後即可提交合作構思及報價。</p>
                <a class="btn btn-accent" href="{{ route('home') }}#start">登入後申請</a>
            @endauth
        </aside>
    </div>
</section>
@endsection
