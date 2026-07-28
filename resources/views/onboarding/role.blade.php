@extends('layouts.app')

@section('title', '選擇角色 — KOLD')

@section('content')
<section class="section">
    <h2>你是哪一方？</h2>
    <p class="lead">選擇後可建立對應檔案，並在探索頁找到對方。</p>
    <div class="grid grid-2">
        <form class="panel" method="POST" action="{{ route('onboarding.role.store') }}">
            @csrf
            <input type="hidden" name="role" value="kol">
            <h3 style="margin-top:0;font-family:var(--font-display)">我是 KOL／創作者</h3>
            <p>公開檔案、連結社群、接受品牌邀請。</p>
            <button class="btn btn-accent" type="submit">以 KOL 繼續</button>
        </form>
        <form class="panel" method="POST" action="{{ route('onboarding.role.store') }}">
            @csrf
            <input type="hidden" name="role" value="brand">
            <h3 style="margin-top:0;font-family:var(--font-display)">我是品牌／廣告主</h3>
            <p>搜尋創作者、送出合作邀請、開啟對話。</p>
            <button class="btn btn-primary" type="submit">以品牌繼續</button>
        </form>
    </div>
</section>
@endsection
