@extends('layouts.app')

@section('title', 'KOLD Beta — 建立你的 KOL 專屬頁面')

@section('meta')
    <meta name="description" content="加入 KOLD Beta，建立你的 KOL 專屬短網址、整理社群連結，並尋找品牌合作機會。">
    <meta property="og:title" content="KOLD Beta — 讓品牌更容易找到你">
    <meta property="og:description" content="大約 5 分鐘建立可分享的 KOL Card，整理社群連結及開始尋找品牌合作。">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ route('beta.index') }}">
    <meta property="og:image" content="{{ url('/assets/kold-beta-invite.png') }}">
@endsection

@section('content')
<section class="beta-hero">
    <div class="beta-hero-inner">
        <span class="beta-kicker">KOLD INVITED BETA</span>
        <h1>建立你嘅 KOL 專屬頁面，<br>令品牌更容易搵到你。</h1>
        <p>用大約 5 分鐘整理 Profile、社群連結同合作特色，完成後即可分享個人短網址及申請品牌合作。</p>

        <div class="beta-login-actions" id="beta-login">
            @guest
                <a class="beta-login beta-login-google" href="{{ route('auth.redirect', 'google') }}">
                    <span aria-hidden="true">G</span> 使用 Google 登入
                </a>
                <a class="beta-login beta-login-meta" href="{{ route('auth.redirect', 'facebook') }}">
                    <span aria-hidden="true">f</span> 使用 Meta 登入
                </a>
            @else
                <a class="beta-login beta-login-primary" href="{{ auth()->user()->hasRole() ? route('dashboard') : route('onboarding.role') }}">
                    繼續設定我的 KOLD
                </a>
            @endguest
        </div>
        <p class="beta-login-note">Meta 登入遇到問題時，可先使用 Google 登入。你的 Card 不需要等待 Meta 審批。</p>

        <div class="beta-progress" aria-label="KOL Card 建立進度預覽">
            <span class="is-done"></span><span class="is-done"></span><span></span><span></span>
        </div>
    </div>
    <div class="beta-card-preview" aria-label="KOL Card 預覽">
        <div class="beta-avatar"><img src="{{ url('/assets/kold-app-icon-1024.png') }}" alt="KOLD"></div>
        <strong>你的名字</strong>
        <span>內容創作者 · Hong Kong</span>
        <div class="beta-preview-tags"><i>生活</i><i>美妝</i><i>短影音</i></div>
        <div class="beta-preview-link">Instagram</div>
        <div class="beta-preview-link">YouTube</div>
        <small>kold.tedku.cloud/k/your-name</small>
    </div>
</section>

<section class="beta-steps" id="how-it-works">
    <div class="beta-section-heading">
        <span>完成第一張 KOL Card</span>
        <h2>四步開始，唔使等 API 審批</h2>
    </div>
    <ol class="beta-step-list">
        <li><b>01</b><div><h3>建立 Profile</h3><p>填寫名稱、簡介、內容類別、地區及語言。</p></div></li>
        <li><b>02</b><div><h3>加入社群連結</h3><p>設定專屬 slug，加入 Instagram、Facebook、YouTube 或作品連結。</p></div></li>
        <li><b>03</b><div><h3>確認 AI 標籤</h3><p>檢視系統建議的內容、受眾及品牌適配標籤，由你決定是否公開。</p></div></li>
        <li><b>04</b><div><h3>預覽及發佈</h3><p>檢查手機版頁面，發佈後即可分享你的 KOLD 短網址。</p></div></li>
    </ol>
</section>

<section class="beta-value">
    <div class="beta-value-copy">
        <span class="beta-kicker">完成後可以</span>
        <h2>一條連結，連接你嘅內容同下一個合作。</h2>
    </div>
    <div class="beta-outcomes">
        <div><strong>01</strong><span>分享個人 KOL Card</span></div>
        <div><strong>02</strong><span>瀏覽品牌合作項目</span></div>
        <div><strong>03</strong><span>接收品牌直接邀請</span></div>
        <div><strong>04</strong><span>提交合作構思及報價</span></div>
    </div>
    <div class="beta-final-actions">
        @guest
            <a class="btn btn-accent" href="{{ route('auth.redirect', 'google') }}">免費建立 KOL Card</a>
        @else
            <a class="btn btn-accent" href="{{ route('profile.edit') }}">繼續建立 KOL Card</a>
        @endguest
        <a class="btn btn-ghost beta-light-button" href="{{ route('projects.index') }}">先睇合作項目</a>
    </div>
</section>

<section class="beta-trust">
    <strong>你控制公開資料</strong>
    <p>未確認的 AI 標籤及私人資料不會公開。你可以先用手動連結完成 Card，之後再選擇連接 Meta Page 或 Instagram Business。</p>
    <div><a href="{{ route('privacy') }}">私隱政策</a><a href="{{ route('terms') }}">使用條款</a><a href="{{ route('data-deletion.instructions') }}">資料刪除</a></div>
</section>
@endsection
