@extends('layouts.app')

@section('title', '管理合作項目 — KOLD')

@section('content')
<section class="section project-page">
    <div class="page-heading">
        <div><span class="eyebrow">Brand Workspace</span><h2>合作項目</h2><p class="lead">建立、公開及處理 KOL 申請。</p></div>
        <a class="btn btn-primary" href="{{ route('projects.create') }}">新增項目</a>
    </div>
    <div class="project-list">
        @forelse ($projects as $project)
            <article class="project-row">
                <div class="project-main">
                    <span class="status-pill status-{{ $project->status }}">{{ ['draft' => '草稿', 'published' => '公開中', 'closed' => '已截止'][$project->status] ?? $project->status }}</span>
                    <h3>{{ $project->title }}</h3>
                    <p>{{ \Illuminate\Support\Str::limit($project->brief, 160) }}</p>
                </div>
                <div class="project-actions">
                    <strong>{{ $project->applications_count }} 份申請</strong>
                    <a class="btn btn-soft" href="{{ route('projects.applications', $project) }}">審核申請</a>
                    <a class="btn btn-ghost" href="{{ route('projects.edit', $project) }}">編輯</a>
                    <a class="btn btn-ghost" href="{{ route('projects.show', $project) }}">預覽</a>
                </div>
            </article>
        @empty
            <div class="empty-state">未有項目。建立第一個草稿後，確認內容再公開。</div>
        @endforelse
    </div>
</section>
@endsection
