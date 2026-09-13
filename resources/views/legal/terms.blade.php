@extends('layouts.app')

@section('title', '使用條款 — KOLD')

@section('content')
<article class="legal-page">
    <header class="legal-heading">
        <p class="eyebrow">KOLD LEGAL</p>
        <h1>使用條款</h1>
        <p>生效日期：2026 年 9 月 12 日</p>
    </header>

    <section>
        <h2>1. 接受條款</h2>
        <p>使用 KOLD 即表示你同意本條款及私隱政策。KOLD 由 {{ config('kold.operator_name') }} 營運。如你無權代表自己或所屬機構接受本條款，請勿使用本服務。</p>
    </section>

    <section>
        <h2>2. 帳戶責任</h2>
        <p>你必須提供準確資料、妥善保護第三方登入帳戶，並對帳戶內的操作負責。不得冒認他人、建立誤導性檔案、使用未獲授權的社群帳戶，或以自動化方式濫用平台。</p>
    </section>

    <section>
        <h2>3. KOL 與品牌內容</h2>
        <p>你保留所提交內容的權利，並授予 KOLD 在營運、展示及推廣相關平台功能所需的有限使用權。你確認有權發布相關文字、圖片、連結及商業資料，內容不得違法、侵權、具欺詐性或造成傷害。</p>
    </section>

    <section>
        <h2>4. 媒合與合作</h2>
        <p>KOLD 提供搜尋、推薦、邀請及對話工具，但不是任何合作的僱主、代理人、付款方或合約一方。KOL 與品牌須自行核實對方、商議報酬、交付、內容使用權、稅務及合約條款。推薦及 AI 標籤只供參考，不保證合作結果。</p>
    </section>

    <section>
        <h2>5. 可接受使用</h2>
        <p>不得利用 KOLD 發放垃圾訊息、收集未授權資料、騷擾用戶、繞過安全措施、上載惡意程式，或進行任何違反適用法律及第三方平台政策的活動。我們可限制或終止涉及風險、濫用或違規的帳戶。</p>
    </section>

    <section>
        <h2>6. 服務及責任</h2>
        <p>我們會合理維護服務，但不保證服務永不中斷或所有資料完全準確。在法律容許的最大範圍內，KOLD 不對用戶之間的交易、第三方平台變更或間接損失負責。本條不排除法律不能排除的責任。</p>
    </section>

    <section>
        <h2>7. 終止、修改及聯絡</h2>
        <p>你可隨時刪除帳戶。我們可能因安全、法律或產品需要更新服務及條款，重大變更會在平台內公布。查詢請電郵 <a href="mailto:{{ config('kold.support_email') }}">{{ config('kold.support_email') }}</a>。</p>
    </section>
</article>
@endsection
