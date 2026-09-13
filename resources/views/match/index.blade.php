@extends('layouts.app')

@section('title', 'AI 搵 KOL — KOLD')

@section('content')
<section class="section match-page">
    <div class="match-heading">
        <div>
            <p class="eyebrow">BRAND MATCHING</p>
            <h2>搵適合呢次合作嘅 KOL</h2>
            <p class="lead">講低產品、受眾同合作方式，KOLD 會整理主要推薦及其他相關候選。</p>
        </div>
    </div>

    <form class="panel match-form" method="GET" action="{{ route('match.index') }}">
        <div class="grid grid-2">
            <div>
                <label for="product">產品或服務</label>
                <input id="product" name="product" value="{{ old('product', $form['product'] ?? '') }}" placeholder="例如：敏感肌護膚產品">
            </div>
            <div>
                <label for="audience">目標客群</label>
                <input id="audience" name="audience" value="{{ old('audience', $form['audience'] ?? '') }}" placeholder="例如：香港 25–34 歲年輕女性">
            </div>
            <div>
                <label for="region">地區</label>
                <input id="region" name="region" value="{{ old('region', $form['region'] ?? '') }}" placeholder="香港">
            </div>
            <div>
                <label for="platform">主要平台</label>
                <select id="platform" name="platform">
                    <option value="">不限平台</option>
                    @foreach (['Instagram', 'YouTube', 'TikTok', 'Facebook'] as $platform)
                        <option value="{{ $platform }}" @selected(($form['platform'] ?? '') === $platform)>{{ $platform }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="collaboration">合作形式</label>
                <input id="collaboration" name="collaboration" value="{{ old('collaboration', $form['collaboration'] ?? '') }}" placeholder="例如：Reels、產品試用、活動出席">
            </div>
            <div class="grid grid-2 budget-fields">
                <div><label for="budget_min">預算下限</label><input id="budget_min" type="number" min="0" name="budget_min" value="{{ old('budget_min', $form['budget_min'] ?? '') }}" placeholder="3000"></div>
                <div><label for="budget_max">預算上限</label><input id="budget_max" type="number" min="0" name="budget_max" value="{{ old('budget_max', $form['budget_max'] ?? '') }}" placeholder="10000"></div>
            </div>
        </div>
        <label for="notes">其他要求</label>
        <textarea id="notes" name="notes" maxlength="1200" placeholder="例如：希望內容自然、以實際使用體驗為主">{{ old('notes', $form['notes'] ?? '') }}</textarea>
        <div class="match-form-actions">
            <a class="btn btn-ghost" href="{{ route('match.index') }}">清除</a>
            <button class="btn btn-primary" type="submit">開始配對</button>
        </div>
    </form>

    @if ($match)
        <div class="match-intent">
            <div>
                <span class="match-mode">{{ ($match['intent']['source'] ?? '') === 'ai' ? 'AI 分析' : '基本配對模式' }}</span>
                <h3>今次搜尋重點</h3>
                <p>{{ $match['intent']['summary'] }}</p>
            </div>
            <div class="meta">
                @foreach ($match['intent']['tags'] as $tag)<span class="chip">{{ $tag }}</span>@endforeach
            </div>
        </div>

        @php
            $groups = [
                ['title' => '主要推薦', 'description' => '內容、品牌方向或合作形式有直接吻合；平台會同時參與排序。', 'results' => $match['primary'], 'primary' => true],
                ['title' => '其他相關', 'description' => '地區、受眾或預算等條件相關，可作更廣泛候選。', 'results' => $match['related'], 'primary' => false],
            ];
        @endphp

        @foreach ($groups as $group)
            @if ($group['results']->isNotEmpty())
                <section class="match-group">
                    <div class="match-group-heading">
                        <div><h3>{{ $group['title'] }}</h3><p>{{ $group['description'] }}</p></div>
                        <span>{{ $group['results']->count() }} 位</span>
                    </div>
                    <div class="match-results">
                        @foreach ($group['results'] as $result)
                            @php($profile = $result['profile'])
                            <article class="match-card {{ $group['primary'] ? 'is-primary' : '' }}">
                                <div class="match-card-person">
                                    <img src="{{ $profile->user->avatar ?: ($profile->photos[0] ?? 'https://picsum.photos/seed/koldmatch/240/240') }}" alt="{{ $profile->display_name }}">
                                    <div>
                                        <h4>{{ $profile->display_name }}</h4>
                                        <p>{{ \Illuminate\Support\Str::limit($profile->bio, 110) }}</p>
                                    </div>
                                </div>
                                <div class="match-card-stats">
                                    <span><strong>{{ number_format($result['followers']) }}</strong> 總粉絲</span>
                                    <span><strong>{{ $profile->rate_min || $profile->rate_max ? number_format((int) ($profile->rate_min ?? 0)).'–'.number_format((int) ($profile->rate_max ?? 0)) : '未提供' }}</strong> 參考報價</span>
                                    <span><strong>{{ count($result['reasons']) }}</strong> 符合條件</span>
                                </div>
                                <div class="meta">
                                    @foreach ($profile->user->socialAccounts->pluck('platform')->unique() as $platform)<span class="chip">{{ ucfirst($platform) }}</span>@endforeach
                                    @foreach ($result['matched_tags'] as $tag)<span class="chip chip-accent">{{ $tag }}</span>@endforeach
                                </div>
                                <ul class="match-reasons">@foreach ($result['reasons'] as $reason)<li>{{ $reason }}</li>@endforeach</ul>
                                <div class="match-card-actions">
                                    <a class="btn btn-primary" href="{{ route('discover.show', $profile->user) }}">查看檔案／發邀請</a>
                                    @if ($profile->slug)<a class="btn btn-soft" href="{{ route('kol-card.show', $profile->slug) }}" target="_blank">公開卡片</a>@endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif
        @endforeach

        @if ($match['results']->isEmpty())
            <div class="panel empty-state">暫時未有符合條件而且已確認標籤嘅 KOL。可以調整合作要求再試。</div>
        @endif
    @endif
</section>
@endsection
