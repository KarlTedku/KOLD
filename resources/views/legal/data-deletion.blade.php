@extends('layouts.app')

@section('title', '刪除資料 — KOLD')

@section('content')
<article class="legal-page">
    <header class="legal-heading">
        <p class="eyebrow">YOUR DATA</p>
        <h1>刪除 KOLD 資料</h1>
        <p>你可以直接刪除帳戶，亦可透過 Meta 撤銷及提出資料刪除要求。</p>
    </header>

    <section>
        <h2>方法一：在 KOLD 刪除帳戶</h2>
        <ol>
            <li>登入 KOLD。</li>
            <li>開啟頁底的「刪除帳戶」。</li>
            <li>閱讀影響，輸入 DELETE 並確認。</li>
            <li>系統會立即刪除帳戶並顯示確認碼。</li>
        </ol>
        @auth
            <a class="btn btn-primary" href="{{ route('account.delete') }}">前往刪除帳戶</a>
        @else
            <a class="btn btn-primary" href="{{ route('home') }}#start">登入 KOLD</a>
        @endauth
    </section>

    <section>
        <h2>方法二：由 Facebook 提出要求</h2>
        <p>在 Facebook 的「設定和私隱」中開啟「應用程式和網站」，選擇 KOLD，移除應用程式並選擇要求刪除相關資料。Meta 會把經簽署的刪除要求傳送到 KOLD，系統完成後會提供確認碼及狀態網址。</p>
    </section>

    <section>
        <h2>會刪除甚麼</h2>
        <p>刪除範圍包括帳戶、KOL 或品牌檔案、公開卡片、links、AI 標籤、社群帳戶及 access tokens、合作邀請、對話和訊息。操作不可復原。基於保安及合規需要，我們只會保留不含個人資料的刪除確認碼及完成時間。</p>
    </section>

    <section>
        <h2>無法登入</h2>
        <p>請使用註冊 KOLD 的電郵地址聯絡 <a href="mailto:{{ config('kold.support_email') }}">{{ config('kold.support_email') }}</a>。我們可能需要核實帳戶擁有權，才可處理要求。</p>
    </section>
</article>
@endsection
