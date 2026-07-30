@extends('layouts.app')

@section('title', '探索 — KOLD')

@section('content')
<section class="section">
    <h2>{{ $mode === 'kol' ? '探索創作者' : '探索品牌' }}</h2>
    <p class="lead">
        @if ($mode === 'kol')
            依關鍵字、niche、地區、粉絲、年齡層與平台篩選公開 KOL。
        @else
            依關鍵字、產業與地區篩選公開品牌。
        @endif
    </p>

    <form class="panel discover-filters" method="GET" action="{{ route('discover.index') }}" style="margin-bottom:1.5rem">
        <div class="grid grid-3">
            <div>
                <label>關鍵字</label>
                <input name="q" value="{{ $q }}">
            </div>
            <div>
                <label>{{ $mode === 'kol' ? 'Niche' : '產業' }}</label>
                <input name="niche" value="{{ $niche }}" placeholder="{{ $mode === 'kol' ? '美妝、旅遊…' : '護膚、科技…' }}">
            </div>
            <div>
                <label>地區</label>
                <input name="region" value="{{ $region }}" placeholder="香港、台灣…">
            </div>
        </div>

        @if ($mode === 'kol')
            <div class="grid grid-3" style="margin-top:.25rem">
                <div>
                    <label>粉絲數</label>
                    <select name="followers">
                        <option value="">全部</option>
                        <option value="under_10k" @selected($followers === 'under_10k')>&lt; 10k</option>
                        <option value="10_50k" @selected($followers === '10_50k')>10k – 50k</option>
                        <option value="50_200k" @selected($followers === '50_200k')>50k – 200k</option>
                        <option value="200k_plus" @selected($followers === '200k_plus')>200k+</option>
                    </select>
                </div>
                <div>
                    <label>年齡層</label>
                    <select name="age_range">
                        <option value="">全部</option>
                        <option value="18-24" @selected($ageRange === '18-24')>18–24</option>
                        <option value="25-34" @selected($ageRange === '25-34')>25–34</option>
                        <option value="35-44" @selected($ageRange === '35-44')>35–44</option>
                    </select>
                </div>
                <div>
                    <label>平台</label>
                    <select name="platform">
                        <option value="">全部</option>
                        <option value="instagram" @selected($platform === 'instagram')>Instagram</option>
                        <option value="youtube" @selected($platform === 'youtube')>YouTube</option>
                        <option value="facebook" @selected($platform === 'facebook')>Facebook</option>
                    </select>
                </div>
            </div>
        @endif

        <button class="btn btn-primary" type="submit">搜尋</button>
    </form>

    <div class="discover-grid">
        @forelse ($profiles as $profile)
            @php($owner = $profile->user)
            <a class="discover-card" href="{{ route('discover.show', $owner) }}">
                @if ($mode === 'kol')
                    <div class="discover-card-media">
                        <img src="{{ $owner->avatar ?: ($profile->photos[0] ?? 'https://picsum.photos/seed/kold/600/750') }}" alt="{{ $profile->display_name }}">
                    </div>
                    <div class="discover-card-body">
                        <h3>{{ $profile->display_name }}</h3>
                        <p>{{ \Illuminate\Support\Str::limit($profile->bio, 90) }}</p>
                        <div class="meta">
                            @if ($profile->age_range)
                                <span class="chip">{{ $profile->age_range }}</span>
                            @endif
                            @foreach ($profile->niches ?? [] as $tag)
                                <span class="chip">{{ $tag }}</span>
                            @endforeach
                        </div>
                        <div class="meta" style="margin-top:.45rem">
                            @foreach ($owner->socialAccounts as $account)
                                <span class="chip">{{ $account->platform }} {{ number_format((int) $account->follower_count) }}</span>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="discover-card-body" style="padding-top:1.1rem">
                        @if ($owner->avatar)
                            <img class="discover-avatar" src="{{ $owner->avatar }}" alt="{{ $profile->company_name }}">
                        @endif
                        <h3>{{ $profile->company_name }}</h3>
                        <p>{{ \Illuminate\Support\Str::limit($profile->bio, 110) }}</p>
                        <div class="meta">
                            @foreach ($profile->industries ?? [] as $tag)
                                <span class="chip">{{ $tag }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </a>
        @empty
            <div class="panel"><p>目前沒有符合條件的公開檔案。</p></div>
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
