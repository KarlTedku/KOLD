@extends('layouts.app')

@section('title', '申請：'.$project->title.' — KOLD')

@section('content')
<section class="section project-page">
    <a class="back-link" href="{{ route('projects.manage') }}">← 返回管理項目</a>
    <div class="page-heading"><div><span class="eyebrow">Applications</span><h2>{{ $project->title }}</h2><p class="lead">審核 KOL 構思；接受後會建立對話並進入洽談中。</p></div></div>
    <div class="application-list">
        @forelse ($applications as $application)
            <article class="application-row">
                <div class="application-person">
                    @if ($application->kol->avatar)<img src="{{ $application->kol->avatar }}" alt="">@endif
                    <div><h3>{{ $application->kol->profileDisplayName() }}</h3><span class="status-pill status-{{ $application->status }}">{{ ['pending' => '待審核', 'accepted' => '已接受', 'declined' => '未獲選', 'withdrawn' => '已撤回'][$application->status] ?? $application->status }}</span></div>
                </div>
                <div class="application-pitch">{!! nl2br(e($application->pitch)) !!}</div>
                <div class="application-actions">
                    <strong>{{ $application->proposed_rate ? 'HK$ '.number_format($application->proposed_rate) : '報價面議' }}</strong>
                    <a class="btn btn-soft" href="{{ route('discover.show', $application->kol) }}">KOL 檔案</a>
                    @if ($application->status === 'pending')
                        <form method="POST" action="{{ route('applications.accept', $application) }}">@csrf<button class="btn btn-primary" type="submit">接受並洽談</button></form>
                        <form method="POST" action="{{ route('applications.decline', $application) }}">@csrf<button class="btn btn-ghost" type="submit">拒絕</button></form>
                    @elseif ($application->collaboration?->conversation)
                        <a class="btn btn-primary" href="{{ route('conversations.show', $application->collaboration->conversation) }}">開啟合作</a>
                    @endif
                </div>
            </article>
        @empty
            <div class="empty-state">暫時未收到申請。</div>
        @endforelse
    </div>
</section>
@endsection
