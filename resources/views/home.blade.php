@extends('layouts.app')

@section('title', 'KOLD — KOL 媒合平台')

@section('content')
<section class="hero">
    <div class="hero-copy">
        <h1 class="hero-brand">KOL<em>D</em></h1>
        <p>讓創作者與品牌互相找到對方。註冊、建檔、搜尋、送出合作邀請——簡單直接的媒合平台。</p>
        <div class="hero-actions" id="start">
            <a class="btn btn-primary" href="{{ route('auth.redirect', 'google') }}">用 Google 開始</a>
            <a class="btn btn-ghost" href="{{ route('auth.redirect', 'facebook') }}">用 Meta 開始</a>
        </div>
    </div>
</section>

<section class="section">
    <h2>先體驗完整流程</h2>
    <p class="lead">SSO 金鑰尚未設定時，可用示範帳號走完建檔、探索與聯絡請求。</p>
    <div class="grid grid-2">
        <form class="panel" method="POST" action="{{ route('auth.demo') }}">
            @csrf
            <input type="hidden" name="role" value="kol">
            <h3 style="margin-top:0;font-family:var(--font-display)">以 KOL 身分進入</h3>
            <p style="opacity:.75">查看品牌、完善檔案、接受合作邀請。</p>
            <button class="btn btn-accent" type="submit">示範登入 · KOL</button>
        </form>
        <form class="panel" method="POST" action="{{ route('auth.demo') }}">
            @csrf
            <input type="hidden" name="role" value="brand">
            <h3 style="margin-top:0;font-family:var(--font-display)">以品牌身分進入</h3>
            <p style="opacity:.75">搜尋創作者、送出聯絡請求、開啟對話。</p>
            <button class="btn btn-primary" type="submit">示範登入 · 品牌</button>
        </form>
    </div>
</section>
@endsection
