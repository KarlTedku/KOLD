@extends('layouts.app')

@section('title', '資料刪除狀態 — KOLD')

@section('content')
<section class="legal-page legal-status">
    <p class="eyebrow">DATA DELETION</p>
    <h1>資料刪除已完成</h1>
    <p>相關 KOLD 帳戶及個人資料已刪除。請保留以下確認碼：</p>
    <code class="confirmation-code">{{ $deletionRequest->confirmation_code }}</code>
    <dl class="status-list">
        <div><dt>狀態</dt><dd>已完成</dd></div>
        <div><dt>完成時間</dt><dd>{{ $deletionRequest->completed_at?->timezone('Australia/Melbourne')->format('Y-m-d H:i') }}</dd></div>
    </dl>
    <a class="btn btn-primary" href="{{ route('home') }}">返回 KOLD</a>
</section>
@endsection
