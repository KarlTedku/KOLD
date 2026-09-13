@extends('layouts.app')

@section('title', '私隱政策 — KOLD')

@section('content')
<article class="legal-page">
    <header class="legal-heading">
        <p class="eyebrow">KOLD LEGAL</p>
        <h1>私隱政策</h1>
        <p>生效日期：2026 年 9 月 12 日</p>
    </header>

    <section>
        <h2>1. 我們是誰</h2>
        <p>KOLD 是由 {{ config('kold.operator_name') }} 營運的 KOL 與品牌媒合平台。本政策說明我們如何收集、使用、保存及刪除你的個人資料。</p>
    </section>

    <section>
        <h2>2. 我們收集的資料</h2>
        <ul>
            <li>登入資料：由 Google 或 Meta 提供的姓名、電郵地址、頭像及平台用戶識別碼。</li>
            <li>個人或品牌檔案：顯示名稱、簡介、地區、語言、內容類別、合作預算或報價。</li>
            <li>社群資料：你手動輸入或授權取得的社群帳號、Page、公開 handle、粉絲數及同步時間。</li>
            <li>平台活動：合作邀請、對話內容、已確認 AI 標籤及公開卡片連結。</li>
            <li>基本技術資料：維持登入、安全及錯誤排查所需的 session、IP 位址和系統紀錄。</li>
        </ul>
    </section>

    <section>
        <h2>3. 資料用途</h2>
        <p>我們只會把資料用於建立及顯示檔案、提供媒合與聯絡功能、同步你授權的社群資料、改善服務、防止濫用，以及履行法律責任。AI 建議標籤必須由 KOL 確認後才會用於商家配對。</p>
    </section>

    <section>
        <h2>4. 公開資料及分享</h2>
        <p>當你發布 KOL Card 或 marketplace profile，當中選擇公開的名稱、簡介、社群連結、標籤及統計資料可由訪客或已登入商家查看。我們不會出售你的個人資料。資料只會在提供服務所需、獲你授權，或法律要求時交予服務供應商或主管機關。</p>
    </section>

    <section>
        <h2>5. 第三方登入及社群平台</h2>
        <p>Google、Facebook 及 Instagram 會按其政策處理你在相關平台的資料。你可在第三方平台撤銷 KOLD 的授權；撤銷授權不會自動刪除已儲存在 KOLD 的帳戶資料，請使用我們的刪除帳戶功能。</p>
    </section>

    <section>
        <h2>6. 保存、安全及你的權利</h2>
        <p>我們只在提供服務及符合法律要求所需期間保存資料，並採取合理技術及管理措施保護資料。你可更新檔案、取消發布公開卡片、解除社群連結，或永久刪除帳戶。帳戶刪除完成後，系統只保留不含個人資料的確認碼及處理紀錄。</p>
    </section>

    <section>
        <h2>7. 聯絡我們</h2>
        <p>如需查閱、更正或查詢個人資料，請電郵 <a href="mailto:{{ config('kold.support_email') }}">{{ config('kold.support_email') }}</a>。</p>
    </section>
</article>
@endsection
