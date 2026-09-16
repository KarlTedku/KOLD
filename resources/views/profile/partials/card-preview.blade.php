<aside class="card-design-preview" aria-label="卡片即時預覽">
    <div class="card-design-preview-heading">
        <div><span>LIVE PREVIEW</span><strong>你嘅卡片</strong></div>
        <a href="{{ route('kol-card.preview') }}" target="_blank" rel="noopener">全頁預覽</a>
    </div>
    <p>{{ $previewHint }}</p>
    <div class="card-preview-phone">
        <iframe src="{{ route('kol-card.preview', ['embedded' => 1]) }}" title="KOL 卡片即時預覽" loading="eager" data-card-preview-frame></iframe>
    </div>
</aside>
