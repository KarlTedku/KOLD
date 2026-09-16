@extends('layouts.card')

@section('title', $profile->display_name.' — KOLD Card')

@section('meta')
    <link rel="canonical" href="{{ route('kol-card.show', $profile->slug) }}">
    <meta name="description" content="{{ $profile->card_headline ?: \Illuminate\Support\Str::limit($profile->bio, 150) }}">
    <meta property="og:title" content="{{ $profile->display_name }} — KOLD">
    <meta property="og:description" content="{{ $profile->card_headline ?: \Illuminate\Support\Str::limit($profile->bio, 150) }}">
    <meta property="og:type" content="profile">
    <meta property="og:url" content="{{ route('kol-card.show', $profile->slug) }}">
    @if ($profile->user->avatar)<meta property="og:image" content="{{ $profile->user->avatar }}">@endif
    @if ($isPreview ?? false)<meta name="robots" content="noindex,nofollow">@endif
@endsection

@section('content')
@php
    $isOwner = auth()->check() && auth()->id() === $profile->user_id;
    $canInvite = auth()->check() && auth()->user()->isBrand();
    $showCardActions = $canInvite || ! auth()->check() || filled($profile->external_contact_url);
    $theme = array_key_exists($profile->card_theme, config('kold.card_themes')) ? $profile->card_theme : 'classic';
    $accent = array_key_exists($profile->card_accent, config('kold.card_accents')) ? $profile->card_accent : 'moss';
    $backgroundUrl = $profile->card_background_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($profile->card_background_path) : null;
    $socialIcons = ['instagram', 'youtube', 'tiktok', 'facebook', 'whatsapp'];
@endphp
<section class="card-page {{ ($isEmbedded ?? false) ? 'is-embedded' : '' }}">
    <div class="kol-card-frame">
        @if (($isPreview ?? false) && ! ($isEmbedded ?? false))
            <div class="preview-banner" role="status">
                <strong>私人預覽</strong>
                <span>呢個畫面只供你檢查，未發布前其他人睇唔到。</span>
            </div>
        @endif

        @if ($isOwner && ! ($isEmbedded ?? false))
            <nav class="kol-card-owner-tools" aria-label="卡片管理">
                <a href="{{ route('profile.edit', ['step' => 'preview']) }}">返回編輯</a>
            </nav>
        @endif

        <article class="kol-card kol-card-theme-{{ $theme }} kol-card-accent-{{ $accent }} {{ $backgroundUrl ? 'has-image' : '' }}" aria-labelledby="kol-card-name" data-kol-card>
        <img class="kol-card-cover" @if ($backgroundUrl) src="{{ $backgroundUrl }}" @endif alt="" aria-hidden="true" @if (! $backgroundUrl) hidden @endif data-kol-card-cover>
        <div class="kol-card-avatar" role="img" aria-label="{{ $profile->display_name }} 的頭像">
            <span aria-hidden="true">{{ mb_substr($profile->display_name, 0, 1) }}</span>
            @if ($profile->user->avatar)
                <img src="{{ $profile->user->avatar }}" alt="" data-kol-card-avatar>
            @endif
        </div>

        <h1 id="kol-card-name">{{ $profile->display_name }}</h1>
        <p class="kol-card-headline" data-kol-card-headline>{{ $profile->card_headline ?: $profile->bio }}</p>

        <div class="meta kol-card-taxonomy">
            @foreach (($profile->niches ?? []) as $tag)
                <span class="chip">{{ $tag }}</span>
            @endforeach
            @foreach (($profile->regions ?? []) as $region)
                <span class="chip">{{ $region }}</span>
            @endforeach
        </div>

        @if ($profile->bio && $profile->bio !== $profile->card_headline)
            <p class="kol-card-bio">{{ $profile->bio }}</p>
        @endif

        @if (! empty($profile->languages))
            <p class="kol-card-languages">語言：{{ implode('、', $profile->languages) }}</p>
        @endif

        @if ($profile->user->socialAccounts->isNotEmpty())
            <div class="kol-card-stats">
                @foreach ($profile->user->socialAccounts as $account)
                    <div>
                        <strong>{{ number_format((int) $account->follower_count) }}</strong>
                        <span>{{ ucfirst($account->platform) }}{{ $account->account_type !== 'manual' ? ' · 已連結' : '' }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($profile->cardLinks->contains(fn ($link) => in_array($link->icon, $socialIcons, true)))
            <nav class="kol-card-socials" aria-label="社交平台">
                @foreach ($profile->cardLinks->filter(fn ($link) => in_array($link->icon, $socialIcons, true))->unique('icon') as $link)
                    <a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $link->title }}">
                        <i class="bi bi-{{ config('kold.card_link_icons.'.$link->icon.'.class') }}" aria-hidden="true"></i>
                    </a>
                @endforeach
            </nav>
        @endif

        <div class="kol-card-links">
            @forelse ($profile->cardLinks as $link)
                <a class="kol-card-link" href="{{ $link->url }}" target="_blank" rel="noopener noreferrer">
                    <i class="bi bi-{{ config('kold.card_link_icons.'.$link->icon.'.class', 'link-45deg') }}" aria-hidden="true"></i>
                    <span>{{ $link->title }}</span>
                    <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                </a>
            @empty
                <p class="empty-card-links">暫時未有公開連結。</p>
            @endforelse
        </div>

        @if ($profile->approvedAiTags->isNotEmpty())
            <section class="kol-card-section">
                <h2>合作特色</h2>
                <div class="meta kol-card-tags">
                    @foreach ($profile->approvedAiTags as $tag)
                        <span class="chip">{{ $tag->label }}</span>
                    @endforeach
                </div>
            </section>
        @endif

        @if (! empty($profile->photos))
            <section class="kol-card-section">
                <h2>精選作品</h2>
                <div class="kol-card-portfolio">
                    @foreach ($profile->photos as $photo)
                        <img src="{{ $photo }}" alt="{{ $profile->display_name }} 的作品">
                    @endforeach
                </div>
            </section>
        @endif

        @if ($showCardActions)
            <div class="kol-card-cta">
                @if ($canInvite)
                    <a class="btn btn-primary" href="{{ route('discover.show', $profile->user) }}">發出合作邀請</a>
                @elseif (! auth()->check())
                    <a class="btn btn-primary" href="{{ route('home') }}#start">登入 KOLD 發合作邀請</a>
                @endif

                @if ($profile->external_contact_url)
                    <a class="btn btn-soft" href="{{ $profile->external_contact_url }}" target="_blank" rel="noopener noreferrer">合作聯絡</a>
                @endif
            </div>
        @endif

        <a class="kol-card-powered" href="{{ route('home') }}">Powered by KOLD</a>
        </article>
    </div>
</section>
<script>
document.querySelectorAll('[data-kol-card-avatar]').forEach((image) => {
    const hideBrokenImage = () => { image.hidden = true; };
    image.addEventListener('error', hideBrokenImage);
    if (image.complete && image.naturalWidth === 0) hideBrokenImage();
});
</script>
@if ($isEmbedded ?? false)
<script>
window.addEventListener('message', (event) => {
    if (event.origin !== window.location.origin || event.source !== window.parent || event.data?.type !== 'kold-card-preview') return;

    const card = document.querySelector('[data-kol-card]');
    const cover = document.querySelector('[data-kol-card-cover]');
    const headline = document.querySelector('[data-kol-card-headline]');
    if (!card || !cover || !headline) return;

    const themes = ['classic', 'spotlight', 'studio'];
    const accents = ['moss', 'coral', 'berry', 'ink'];
    if (themes.includes(event.data.theme)) {
        themes.forEach((theme) => card.classList.remove('kol-card-theme-' + theme));
        card.classList.add('kol-card-theme-' + event.data.theme);
    }
    if (accents.includes(event.data.accent)) {
        accents.forEach((accent) => card.classList.remove('kol-card-accent-' + accent));
        card.classList.add('kol-card-accent-' + event.data.accent);
    }
    if (typeof event.data.headline === 'string') headline.textContent = event.data.headline.slice(0, 160);
    if (typeof event.data.backgroundUrl === 'string' && event.data.backgroundUrl.startsWith('data:image/')) {
        cover.src = event.data.backgroundUrl;
        cover.hidden = false;
        card.classList.add('has-image');
    }
});
</script>
@endif
@endsection
