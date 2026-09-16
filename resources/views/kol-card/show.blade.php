@extends('layouts.card')

@section('title', $profile->display_name.' — KOLD Card')

@section('meta')
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
@endphp
<section class="card-page">
    <div class="kol-card-frame">
        @if ($isPreview ?? false)
            <div class="preview-banner" role="status">
                <strong>私人預覽</strong>
                <span>呢個畫面只供你檢查，未發布前其他人睇唔到。</span>
            </div>
        @endif

        @if ($isOwner)
            <nav class="kol-card-owner-tools" aria-label="卡片管理">
                <a href="{{ route('profile.edit', ['step' => 'preview']) }}">返回編輯</a>
            </nav>
        @endif

        <article class="kol-card" aria-labelledby="kol-card-name">
        @if ($profile->user->avatar)
            <img class="kol-card-avatar" src="{{ $profile->user->avatar }}" alt="{{ $profile->display_name }}">
        @endif

        <h1 id="kol-card-name">{{ $profile->display_name }}</h1>
        <p class="kol-card-headline">{{ $profile->card_headline ?: $profile->bio }}</p>

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

        <div class="kol-card-links">
            @forelse ($profile->cardLinks as $link)
                <a class="kol-card-link" href="{{ $link->url }}" target="_blank" rel="noopener noreferrer">
                    {{ $link->title }}
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
@endsection
