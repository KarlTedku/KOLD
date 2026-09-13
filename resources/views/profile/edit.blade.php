@extends('layouts.app')

@section('title', '我的檔案 — KOLD')

@section('content')
<section class="section profile-builder">
    @if ($user->isKol())
        @php
            $profile = $user->kolProfile;
            $steps = [
                'profile' => ['1', '基本資料'],
                'card' => ['2', '卡片網址'],
                'links' => ['3', '連結'],
                'tags' => ['4', 'AI 標籤'],
                'preview' => ['5', '預覽發布'],
            ];
        @endphp

        <div class="builder-heading">
            <div>
                <p class="eyebrow">KOL CARD BUILDER</p>
                <h2>建立你嘅公開卡片</h2>
                <p class="lead">完成資料、連結同標籤，就可以分享一個集中展示所有頻道嘅網址。</p>
            </div>
            <span class="publish-state {{ $profile?->isPublished() ? 'is-live' : '' }}">
                {{ $profile?->isPublished() ? '已發布' : '草稿' }}
            </span>
        </div>

        <nav class="builder-steps" aria-label="KOL 卡片設定進度">
            @foreach ($steps as $key => [$number, $label])
                <a class="builder-step {{ $step === $key ? 'is-active' : '' }} {{ $progress[$key] ?? false ? 'is-complete' : '' }}"
                   href="{{ route('profile.edit', ['step' => $key]) }}">
                    <span>{{ $progress[$key] ?? false ? '✓' : $number }}</span>
                    <strong>{{ $label }}</strong>
                </a>
            @endforeach
        </nav>

        <div class="builder-content panel">
            @if ($step === 'profile')
                <div class="builder-section-heading">
                    <div><span>步驟 1 / 5</span><h3>基本資料</h3></div>
                    <p>呢啲資料會用喺公開檔案同 AI 標籤建議。</p>
                </div>
                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf
                    @method('PUT')
                    <label for="display_name">顯示名稱</label>
                    <input id="display_name" name="display_name" value="{{ old('display_name', $profile?->display_name) }}" required>
                    <label for="bio">簡介</label>
                    <textarea id="bio" name="bio" placeholder="簡單介紹你嘅內容方向同特色">{{ old('bio', $profile?->bio) }}</textarea>
                    <div class="grid grid-2">
                        <div>
                            <label for="niches">內容類別</label>
                            <input id="niches" name="niches" value="{{ old('niches', implode('，', $profile?->niches ?? [])) }}" placeholder="美妝，生活，短影音">
                        </div>
                        <div>
                            <label for="regions">主要地區</label>
                            <input id="regions" name="regions" value="{{ old('regions', implode('，', $profile?->regions ?? [])) }}" placeholder="香港">
                        </div>
                        <div>
                            <label for="languages">語言</label>
                            <input id="languages" name="languages" value="{{ old('languages', implode('，', $profile?->languages ?? [])) }}" placeholder="粵語，繁中">
                        </div>
                        <div>
                            <label for="age_range">年齡層</label>
                            <select id="age_range" name="age_range">
                                <option value="">未設定</option>
                                @foreach (['18-24', '25-34', '35-44'] as $range)
                                    <option value="{{ $range }}" @selected(old('age_range', $profile?->age_range) === $range)>{{ $range }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <label for="photos">作品圖 URL（逗號分隔）</label>
                    <textarea id="photos" name="photos" placeholder="https://...">{{ old('photos', implode('，', $profile?->photos ?? [])) }}</textarea>
                    <div class="grid grid-2">
                        <div><label for="rate_min">報價下限</label><input id="rate_min" type="number" min="0" name="rate_min" value="{{ old('rate_min', $profile?->rate_min) }}"></div>
                        <div><label for="rate_max">報價上限</label><input id="rate_max" type="number" min="0" name="rate_max" value="{{ old('rate_max', $profile?->rate_max) }}"></div>
                    </div>
                    <div class="builder-actions"><span></span><button class="btn btn-primary" type="submit">儲存並繼續</button></div>
                </form>

                <details class="ai-draft-box">
                    <summary>用 AI 幫我整理簡介</summary>
                    <form method="POST" action="{{ route('profile.ai') }}">
                        @csrf
                        <label for="ai_notes">補充說明</label>
                        <textarea id="ai_notes" name="notes" placeholder="例如：主打香港美妝、喜歡真實測評"></textarea>
                        <button class="btn btn-soft" type="submit">產生草稿</button>
                    </form>
                </details>
            @elseif ($step === 'card')
                <div class="builder-section-heading">
                    <div><span>步驟 2 / 5</span><h3>卡片網址與介紹</h3></div>
                    <p>設定一個容易分享嘅網址，同一句最能代表你嘅介紹。</p>
                </div>
                <form method="POST" action="{{ route('kol-card.update') }}">
                    @csrf
                    @method('PUT')
                    <label for="slug">公開短網址</label>
                    <div class="slug-field"><span>{{ url('/k') }}/</span><input id="slug" name="slug" value="{{ old('slug', $profile?->slug ?: \Illuminate\Support\Str::slug($user->profileDisplayName())) }}" placeholder="mina-daily" required></div>
                    <p class="field-help">只可使用英文字母、數字同連字號，最少 3 個字元。</p>
                    <label for="card_headline">一句介紹</label>
                    <input id="card_headline" name="card_headline" maxlength="160" value="{{ old('card_headline', $profile?->card_headline) }}" placeholder="香港美妝與生活創作者">
                    <label for="external_contact_url">合作聯絡連結</label>
                    <input id="external_contact_url" name="external_contact_url" type="url" value="{{ old('external_contact_url', $profile?->external_contact_url) }}" placeholder="https://wa.me/...">
                    <div class="builder-actions">
                        <a class="btn btn-ghost" href="{{ route('profile.edit', ['step' => 'profile']) }}">上一步</a>
                        <button class="btn btn-primary" type="submit">儲存並繼續</button>
                    </div>
                </form>
            @elseif ($step === 'links')
                <div class="builder-section-heading">
                    <div><span>步驟 3 / 5</span><h3>卡片連結</h3></div>
                    <p>加入 Instagram、YouTube、作品集或其他希望品牌睇到嘅頁面。</p>
                </div>
                <form method="POST" action="{{ route('kol-card.links.social') }}" class="inline-action">
                    @csrf
                    <button class="btn btn-soft" type="submit">由社群帳號加入</button>
                    <a class="btn btn-ghost" href="{{ route('social.index') }}">管理社群帳號</a>
                </form>
                <form method="POST" action="{{ route('kol-card.links.store') }}" class="link-create-form">
                    @csrf
                    <div class="grid grid-2">
                        <div><label for="link_title">顯示文字</label><input id="link_title" name="title" placeholder="Instagram / YouTube / Media Kit" required></div>
                        <div><label for="link_url">網址</label><input id="link_url" name="url" type="url" placeholder="https://..." required></div>
                    </div>
                    <input type="hidden" name="type" value="custom">
                    <button class="btn btn-primary" type="submit">新增連結</button>
                </form>
                <div class="link-list">
                    @forelse ($profile?->cardLinks ?? [] as $link)
                        <div class="link-editor">
                            <form method="POST" action="{{ route('kol-card.links.update', $link) }}">
                                @csrf
                                @method('PUT')
                                <div class="grid link-editor-grid">
                                    <div><label>顯示文字</label><input name="title" value="{{ $link->title }}" required></div>
                                    <div><label>網址</label><input name="url" type="url" value="{{ $link->url }}" required></div>
                                    <div><label>次序</label><input type="number" name="sort_order" min="0" max="999" value="{{ $link->sort_order }}" required></div>
                                </div>
                                <div class="link-editor-actions">
                                    <label class="toggle-label"><input type="checkbox" name="is_active" value="1" @checked($link->is_active)> 顯示</label>
                                    <button class="btn btn-soft" type="submit">更新</button>
                                </div>
                            </form>
                            <form method="POST" action="{{ route('kol-card.links.destroy', $link) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-ghost" type="submit">刪除</button>
                            </form>
                        </div>
                    @empty
                        <div class="empty-state">未有連結。加入最少一個連結後先可以發布。</div>
                    @endforelse
                </div>
                <div class="builder-actions">
                    <a class="btn btn-ghost" href="{{ route('profile.edit', ['step' => 'card']) }}">上一步</a>
                    <a class="btn btn-primary" href="{{ route('profile.edit', ['step' => 'tags']) }}">下一步</a>
                </div>
            @elseif ($step === 'tags')
                @php($categoryNames = ['content' => '內容類別', 'collaboration' => '合作形式', 'brand_fit' => '品牌適配', 'audience' => '受眾', 'region' => '地區', 'tone' => '內容風格', 'platform' => '平台強項'])
                <div class="builder-section-heading">
                    <div><span>步驟 4 / 5</span><h3>AI 配對標籤</h3></div>
                    <p>AI 只會提出建議；你確認後，品牌搜尋先會使用。</p>
                </div>
                <div class="inline-action">
                    <form method="POST" action="{{ route('ai-tags.generate') }}">@csrf<button class="btn btn-accent" type="submit">產生建議標籤</button></form>
                    @if (($profile?->aiTags ?? collect())->contains('status', 'suggested'))
                        <form method="POST" action="{{ route('ai-tags.approve-all') }}">@csrf<button class="btn btn-soft" type="submit">確認所有建議</button></form>
                    @endif
                </div>
                <div class="tag-review-list">
                    @forelse (($profile?->aiTags ?? collect())->sortBy('status') as $tag)
                        <div class="tag-review-item">
                            <div>
                                <strong>{{ $tag->label }}</strong>
                                <div class="meta"><span class="chip">{{ $categoryNames[$tag->category] ?? $tag->category }}</span><span class="chip">{{ $tag->status === 'approved' ? '已確認' : '待確認' }}</span><span class="chip">{{ $tag->confidence }}%</span></div>
                                @if ($tag->rationale)<p>{{ $tag->rationale }}</p>@endif
                            </div>
                            <div class="inline-action">
                                @if ($tag->status !== 'approved')<form method="POST" action="{{ route('ai-tags.approve', $tag) }}">@csrf<button class="btn btn-soft" type="submit">確認</button></form>@endif
                                <form method="POST" action="{{ route('ai-tags.reject', $tag) }}">@csrf @method('DELETE')<button class="btn btn-ghost" type="submit">移除</button></form>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state">未有 AI 標籤。你可以產生建議，亦可以略過呢一步。</div>
                    @endforelse
                </div>
                <div class="builder-actions">
                    <a class="btn btn-ghost" href="{{ route('profile.edit', ['step' => 'links']) }}">上一步</a>
                    <a class="btn btn-primary" href="{{ route('profile.edit', ['step' => 'preview']) }}">預覽卡片</a>
                </div>
            @else
                @php($issues = $profile?->cardPublicationIssues() ?? ['請先完成基本資料。'])
                <div class="builder-section-heading">
                    <div><span>步驟 5 / 5</span><h3>預覽及發布</h3></div>
                    <p>發布後任何人都可以透過你嘅短網址查看卡片。</p>
                </div>
                <div class="preview-summary">
                    <div><span>公開網址</span><strong>{{ $profile?->slug ? url('/k/'.$profile->slug) : '尚未設定' }}</strong></div>
                    <div><span>啟用連結</span><strong>{{ $profile?->cardLinks->where('is_active', true)->count() ?? 0 }}</strong></div>
                    <div><span>已確認標籤</span><strong>{{ $profile?->aiTags->where('status', 'approved')->count() ?? 0 }}</strong></div>
                </div>
                @if ($issues !== [])
                    <div class="readiness-box">
                        <strong>發布前仍需完成：</strong>
                        <ul>@foreach ($issues as $issue)<li>{{ $issue }}</li>@endforeach</ul>
                    </div>
                @else
                    <div class="readiness-box is-ready">卡片已準備好，可以發布。</div>
                @endif
                <div class="builder-actions preview-actions">
                    <a class="btn btn-ghost" href="{{ route('profile.edit', ['step' => 'tags']) }}">上一步</a>
                    <a class="btn btn-soft" href="{{ route('kol-card.preview') }}" target="_blank">開啟預覽</a>
                    @if ($profile?->isPublished())
                        <a class="btn btn-primary" href="{{ route('kol-card.show', $profile->slug) }}" target="_blank">查看公開卡片</a>
                        <form method="POST" action="{{ route('kol-card.unpublish') }}">@csrf<button class="btn btn-ghost" type="submit">取消發布</button></form>
                    @else
                        <form method="POST" action="{{ route('kol-card.publish') }}">@csrf<button class="btn btn-primary" type="submit" @disabled($issues !== [])>發布卡片</button></form>
                    @endif
                </div>
            @endif
        </div>
    @else
        @php($profile = $user->brandProfile)
        <h2>品牌檔案</h2>
        <p class="lead">完善品牌資料，令 KOL 更容易理解你嘅合作方向。</p>
        <form class="panel profile-form-narrow" method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PUT')
            <label>公司／品牌名稱</label><input name="company_name" value="{{ old('company_name', $profile?->company_name) }}" required>
            <label>簡介</label><textarea name="bio">{{ old('bio', $profile?->bio) }}</textarea>
            <label>產業（逗號分隔）</label><input name="industries" value="{{ old('industries', implode('，', $profile?->industries ?? [])) }}">
            <label>地區</label><input name="regions" value="{{ old('regions', implode('，', $profile?->regions ?? [])) }}">
            <label>預算區間</label><input name="budget_range" value="{{ old('budget_range', $profile?->budget_range) }}">
            <label>狀態</label><select name="status"><option value="draft" @selected(old('status', $profile?->status) === 'draft')>草稿</option><option value="published" @selected(old('status', $profile?->status) === 'published')>發佈</option></select>
            <button class="btn btn-primary" type="submit">儲存品牌檔案</button>
        </form>
    @endif
</section>
@endsection
