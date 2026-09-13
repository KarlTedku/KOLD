@extends('layouts.app')

@section('title', '刪除帳戶 — KOLD')

@section('content')
<section class="legal-page danger-zone">
    <p class="eyebrow">ACCOUNT</p>
    <h1>永久刪除帳戶</h1>
    <p>你正準備刪除 <strong>{{ $user->email }}</strong>。此操作會永久刪除你的 Profile、公開 KOL Card、社群連結及 tokens、合作邀請、對話和訊息，而且不能復原。</p>

    <form method="POST" action="{{ route('account.destroy') }}" class="panel deletion-form">
        @csrf
        @method('DELETE')
        <label for="confirmation">輸入 <strong>DELETE</strong> 確認</label>
        <input id="confirmation" name="confirmation" autocomplete="off" required>
        <div class="form-actions">
            <a class="btn btn-ghost" href="{{ route('profile.edit') }}">取消</a>
            <button class="btn btn-danger" type="submit">永久刪除帳戶</button>
        </div>
    </form>
</section>
@endsection
