@extends('layouts.app')

@section('title', '總覽 — KOLD')

@section('content')
<section class="section">
    <h2>你好，{{ $user->profileDisplayName() }}</h2>
    <p class="lead">
        身分：{{ $user->isKol() ? 'KOL' : '品牌' }}
        · 檔案狀態：{{ $user->isPublished() ? '已發佈' : '草稿' }}
        · 待處理邀請：{{ $pendingInbox }}
    </p>

    <div class="grid grid-3">
        <a class="panel" href="{{ route('profile.edit') }}">
            <h3 style="margin-top:0;font-family:var(--font-display)">{{ $user->isKol() ? 'KOL 卡片' : '完善檔案' }}</h3>
            <p>{{ $user->isKol() ? '建立可分享卡片、連結與 AI matching 標籤。' : '手動編輯，或用 AI 產生草稿。' }}</p>
        </a>
        <a class="panel" href="{{ route('discover.index') }}">
            <h3 style="margin-top:0;font-family:var(--font-display)">去探索</h3>
            <p>搜尋並瀏覽對方公開檔案。</p>
        </a>
        @if ($user->isBrand())
            <a class="panel" href="{{ route('projects.manage') }}">
                <h3 style="margin-top:0;font-family:var(--font-display)">合作項目</h3>
                <p>發佈 Project 並審核 KOL 申請。</p>
            </a>
        @else
            <a class="panel" href="{{ route('projects.index') }}">
                <h3 style="margin-top:0;font-family:var(--font-display)">搵合作</h3>
                <p>瀏覽公開 Project，登入後直接申請。</p>
            </a>
        @endif
    </div>
</section>
@endsection
