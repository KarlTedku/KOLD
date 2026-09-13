@extends('layouts.app')

@section('title', ($project->exists ? '編輯' : '新增').'合作項目 — KOLD')

@section('content')
<section class="section project-page">
    <a class="back-link" href="{{ route('projects.manage') }}">← 返回管理項目</a>
    <div class="page-heading"><div><span class="eyebrow">Brand Project</span><h2>{{ $project->exists ? '編輯合作項目' : '新增合作項目' }}</h2><p class="lead">先儲存草稿，確認後再公開畀 KOL 申請。</p></div></div>

    <form class="project-form" method="POST" action="{{ $project->exists ? route('projects.update', $project) : route('projects.store') }}">
        @csrf
        @if ($project->exists) @method('PUT') @endif
        <label for="title">項目名稱</label>
        <input id="title" name="title" required maxlength="140" value="{{ old('title', $project->title) }}">
        <label for="brief">合作簡介</label>
        <textarea id="brief" name="brief" required minlength="20" maxlength="5000" rows="7">{{ old('brief', $project->brief) }}</textarea>
        <div class="form-grid">
            <div><label for="niches">內容類別</label><input id="niches" name="niches" value="{{ old('niches', implode('、', $project->niches ?? [])) }}" placeholder="美妝、生活、旅遊"></div>
            <div><label for="regions">地區</label><input id="regions" name="regions" value="{{ old('regions', implode('、', $project->regions ?? [])) }}" placeholder="香港、九龍"></div>
        </div>
        <fieldset class="platform-picker">
            <legend>目標平台</legend>
            @foreach (['instagram' => 'Instagram', 'facebook' => 'Facebook', 'youtube' => 'YouTube'] as $value => $label)
                <label><input type="checkbox" name="platforms[]" value="{{ $value }}" @checked(in_array($value, old('platforms', $project->platforms ?? []), true))> {{ $label }}</label>
            @endforeach
        </fieldset>
        <div class="form-grid">
            <div><label for="budget_min">預算下限（HK$）</label><input id="budget_min" name="budget_min" type="number" min="0" value="{{ old('budget_min', $project->budget_min) }}"></div>
            <div><label for="budget_max">預算上限（HK$）</label><input id="budget_max" name="budget_max" type="number" min="0" value="{{ old('budget_max', $project->budget_max) }}"></div>
        </div>
        <label for="deliverables">交付要求</label>
        <textarea id="deliverables" name="deliverables" maxlength="3000" rows="4">{{ old('deliverables', $project->deliverables) }}</textarea>
        <div class="form-grid form-grid-3">
            <div><label for="application_deadline">申請截止</label><input id="application_deadline" name="application_deadline" type="date" value="{{ old('application_deadline', $project->application_deadline?->format('Y-m-d')) }}"></div>
            <div><label for="campaign_start_date">開始日期</label><input id="campaign_start_date" name="campaign_start_date" type="date" value="{{ old('campaign_start_date', $project->campaign_start_date?->format('Y-m-d')) }}"></div>
            <div><label for="campaign_end_date">完成日期</label><input id="campaign_end_date" name="campaign_end_date" type="date" value="{{ old('campaign_end_date', $project->campaign_end_date?->format('Y-m-d')) }}"></div>
        </div>
        <div class="project-form-actions"><button class="btn btn-primary" type="submit">儲存草稿</button></div>
    </form>

    @if ($project->exists)
        <div class="publish-bar">
            <div><strong>{{ $project->status === 'published' ? '項目現正公開' : '項目未有接受申請' }}</strong><span>公開後，所有訪客可瀏覽，只有登入 KOL 可以申請。</span></div>
            @if ($project->status !== 'published')
                <form method="POST" action="{{ route('projects.publish', $project) }}">@csrf<button class="btn btn-accent" type="submit">公開項目</button></form>
            @else
                <form method="POST" action="{{ route('projects.close', $project) }}">@csrf<button class="btn btn-ghost" type="submit">截止申請</button></form>
            @endif
        </div>
    @endif
</section>
@endsection
