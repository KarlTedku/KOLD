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
            <h3 style="margin-top:0;font-family:var(--font-display)">完善檔案</h3>
            <p>手動編輯，或用 AI 產生草稿。</p>
        </a>
        <a class="panel" href="{{ route('discover.index') }}">
            <h3 style="margin-top:0;font-family:var(--font-display)">去探索</h3>
            <p>搜尋並瀏覽對方公開檔案。</p>
        </a>
        <a class="panel" href="{{ route('contact.inbox') }}">
            <h3 style="margin-top:0;font-family:var(--font-display)">收件匣</h3>
            <p>處理合作邀請與回覆。</p>
        </a>
    </div>
</section>
@endsection
