@extends('layouts.app')

@section('title', '探索 — KOLD')

@section('content')
<section class="section">
    <h2>{{ $mode === 'kol' ? '探索創作者' : '探索品牌' }}</h2>
    <p class="lead">依關鍵字、niche／產業與地區篩選公開檔案。</p>

    <form class="panel" method="GET" action="{{ route('discover.index') }}" style="margin-bottom:1.5rem">
        <div class="grid grid-3">
            <div>
                <label>關鍵字</label>
                <input name="q" value="{{ $q }}">
            </div>
            <div>
                <label>{{ $mode === 'kol' ? 'Niche' : '產業' }}</label>
                <input name="niche" value="{{ $niche }}">
            </div>
            <div>
                <label>地區</label>
                <input name="region" value="{{ $region }}">
            </div>
        </div>
        <button class="btn btn-primary" type="submit">搜尋</button>
    </form>

    <div class="panel">
        @forelse ($profiles as $profile)
            @php($owner = $profile->user)
            <div class="profile-row">
                <div>
                    <h3>
                        <a href="{{ route('discover.show', $owner) }}">
                            {{ $mode === 'kol' ? $profile->display_name : $profile->company_name }}
                        </a>
                    </h3>
                    <p style="margin:.35rem 0;opacity:.78">{{ \Illuminate\Support\Str::limit($profile->bio, 120) }}</p>
                    <div class="meta">
                        @foreach (($mode === 'kol' ? ($profile->niches ?? []) : ($profile->industries ?? [])) as $tag)
                            <span class="chip">{{ $tag }}</span>
                        @endforeach
                        @foreach ($owner->socialAccounts as $account)
                            <span class="chip">{{ $account->platform }} {{ number_format((int) $account->follower_count) }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        @empty
            <p>目前沒有符合條件的公開檔案。</p>
        @endforelse
    </div>

    <div style="margin-top:1rem;display:flex;gap:.75rem;align-items:center">
        @if ($profiles->onFirstPage())
            <span class="btn btn-ghost" style="opacity:.45">上一頁</span>
        @else
            <a class="btn btn-ghost" href="{{ $profiles->previousPageUrl() }}">上一頁</a>
        @endif
        <span>第 {{ $profiles->currentPage() }} 頁</span>
        @if ($profiles->hasMorePages())
            <a class="btn btn-ghost" href="{{ $profiles->nextPageUrl() }}">下一頁</a>
        @else
            <span class="btn btn-ghost" style="opacity:.45">下一頁</span>
        @endif
    </div>
</section>
@endsection
