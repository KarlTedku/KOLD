@extends('layouts.app')

@section('title', '我的檔案 — KOLD')

@section('content')
<section class="section profile-builder">
    @if ($user->isKol())
        @php
            $profile = $user->kolProfile;
            $profileOptions = config('kold.profile_options');
            $steps = [
                'profile' => ['1', '基本資料'],
                'card' => ['2', '卡片網址'],
                'links' => ['3', '連結'],
                'tags' => ['4', 'AI 標籤'],
                'preview' => ['5', '預覽發布'],
            ];
            $selectionState = function (string $field, array $stored, array $options, array $aliases): array {
                $submitted = old($field, $stored);
                $submitted = is_array($submitted) ? $submitted : [];
                $normalized = collect($submitted)
                    ->map(fn (string $value): string => $aliases[$value] ?? $value)
                    ->unique()
                    ->values()
                    ->all();
                $known = array_values(array_intersect($normalized, array_keys($options)));
                $custom = array_values(array_diff($normalized, array_keys($options), ['other']));
                $otherValue = old($field.'_other', implode('、', $custom));

                if (in_array('other', $normalized, true) || $custom !== [] || filled($otherValue)) {
                    $known[] = 'other';
                }

                return [array_values(array_unique($known)), $otherValue];
            };
            [$selectedNiches, $nichesOther] = $selectionState('niches', $profile?->niches ?? [], $profileOptions['niches'], $profileOptions['legacy_aliases']['niches']);
            [$selectedRegions, $regionsOther] = $selectionState('regions', $profile?->regions ?? [], $profileOptions['regions'], $profileOptions['legacy_aliases']['regions']);
            [$selectedLanguages, $languagesOther] = $selectionState('languages', $profile?->languages ?? [], $profileOptions['languages'], $profileOptions['legacy_aliases']['languages']);
            $matchedRateRange = collect($profileOptions['rate_ranges'])->search(
                fn (array $range): bool => $range['min'] === $profile?->rate_min && $range['max'] === $profile?->rate_max
            );
            $hasExistingRate = $profile && ($profile->rate_min !== null || $profile->rate_max !== null);
            $selectedRateRange = old('rate_range', $matchedRateRange !== false ? $matchedRateRange : ($hasExistingRate ? 'existing' : 'negotiable'));
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
                @php
                    $avatarSourceLabel = match ($user->avatar_source) {
                        \App\Models\User::AVATAR_SOURCE_MANUAL => '你上載嘅圖片',
                        \App\Models\User::AVATAR_SOURCE_META => '主要 Instagram 專業帳戶',
                        \App\Models\User::AVATAR_SOURCE_OAUTH => '登入帳戶圖片',
                        default => '未設定',
                    };
                @endphp
                <div class="avatar-editor">
                    <div class="avatar-editor-preview" aria-hidden="true">
                        @if ($user->avatar)
                            <img src="{{ $user->avatar }}" alt="">
                        @else
                            <span>{{ mb_strtoupper(mb_substr($user->profileDisplayName(), 0, 1)) }}</span>
                        @endif
                    </div>
                    <div class="avatar-editor-body">
                        <div class="avatar-editor-heading">
                            <div>
                                <span class="avatar-editor-kicker">公開頭像</span>
                                <h4>揀一張代表你嘅相</h4>
                            </div>
                            <span class="avatar-source">現時：{{ $avatarSourceLabel }}</span>
                        </div>
                        <p>支援 JPG、PNG 或 WebP，最大 5MB。手動上載後，之後重新登入或連結 OAuth 都唔會自動覆蓋。</p>
                        <div class="avatar-editor-actions">
                            <form class="avatar-upload-form" method="POST" action="{{ route('profile.avatar.update') }}" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <label for="avatar">選擇圖片</label>
                                <input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" required>
                                <button class="btn btn-primary" type="submit">上載新頭像</button>
                            </form>
                            @if ($metaAvatarUrl)
                                <form method="POST" action="{{ route('profile.avatar.meta') }}">
                                    @csrf
                                    <button class="btn btn-soft" type="submit">使用主要 Instagram 頭像</button>
                                </form>
                            @else
                                <a class="btn btn-ghost" href="{{ route('social.index') }}">連結 Meta／Instagram</a>
                            @endif
                        </div>
                        @error('avatar')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                </div>
                <form class="profile-structured-form" method="POST" action="{{ route('profile.update') }}" data-profile-structured-form>
                    @csrf
                    @method('PUT')
                    <label for="display_name">顯示名稱</label>
                    <input id="display_name" name="display_name" value="{{ old('display_name', $profile?->display_name) }}" required>
                    <label for="bio">簡介</label>
                    <textarea id="bio" name="bio" placeholder="簡單介紹你嘅內容方向同特色">{{ old('bio', $profile?->bio) }}</textarea>
                    <fieldset class="choice-group" data-other-group>
                        <legend>內容類別 <span class="field-hint">可選多項</span></legend>
                        <div class="choice-cards choice-cards-3">
                            @foreach ($profileOptions['niches'] as $value => $label)
                                <label class="choice-card"><input type="checkbox" name="niches[]" value="{{ $value }}" @checked(in_array($value, $selectedNiches, true))><span>{{ $label }}</span></label>
                            @endforeach
                            <label class="choice-card"><input type="checkbox" name="niches[]" value="other" @checked(in_array('other', $selectedNiches, true)) data-other-toggle><span>其他</span></label>
                        </div>
                        <div class="other-choice-field" data-other-field>
                            <label for="niches_other">其他內容類別</label>
                            <input id="niches_other" name="niches_other" value="{{ $nichesOther }}" maxlength="120" placeholder="可用逗號分隔">
                        </div>
                        @error('niches')<p class="field-error">{{ $message }}</p>@enderror
                        @error('niches.*')<p class="field-error">{{ $message }}</p>@enderror
                        @error('niches_other')<p class="field-error">{{ $message }}</p>@enderror
                    </fieldset>

                    <fieldset class="choice-group" data-other-group>
                        <legend>主要地區 <span class="field-hint">可選多項</span></legend>
                        <div class="choice-cards choice-cards-3">
                            @foreach ($profileOptions['regions'] as $value => $label)
                                <label class="choice-card"><input type="checkbox" name="regions[]" value="{{ $value }}" @checked(in_array($value, $selectedRegions, true))><span>{{ $label }}</span></label>
                            @endforeach
                            <label class="choice-card"><input type="checkbox" name="regions[]" value="other" @checked(in_array('other', $selectedRegions, true)) data-other-toggle><span>其他</span></label>
                        </div>
                        <div class="other-choice-field" data-other-field>
                            <label for="regions_other">其他地區</label>
                            <input id="regions_other" name="regions_other" value="{{ $regionsOther }}" maxlength="120" placeholder="可用逗號分隔">
                        </div>
                        @error('regions')<p class="field-error">{{ $message }}</p>@enderror
                        @error('regions.*')<p class="field-error">{{ $message }}</p>@enderror
                        @error('regions_other')<p class="field-error">{{ $message }}</p>@enderror
                    </fieldset>

                    <fieldset class="choice-group" data-other-group>
                        <legend>語言 <span class="field-hint">可選多項</span></legend>
                        <div class="choice-cards choice-cards-3">
                            @foreach ($profileOptions['languages'] as $value => $label)
                                <label class="choice-card"><input type="checkbox" name="languages[]" value="{{ $value }}" @checked(in_array($value, $selectedLanguages, true))><span>{{ $label }}</span></label>
                            @endforeach
                            <label class="choice-card"><input type="checkbox" name="languages[]" value="other" @checked(in_array('other', $selectedLanguages, true)) data-other-toggle><span>其他</span></label>
                        </div>
                        <div class="other-choice-field" data-other-field>
                            <label for="languages_other">其他語言</label>
                            <input id="languages_other" name="languages_other" value="{{ $languagesOther }}" maxlength="120" placeholder="可用逗號分隔">
                        </div>
                        @error('languages')<p class="field-error">{{ $message }}</p>@enderror
                        @error('languages.*')<p class="field-error">{{ $message }}</p>@enderror
                        @error('languages_other')<p class="field-error">{{ $message }}</p>@enderror
                    </fieldset>

                    <div class="profile-single-select">
                        <label for="age_range">年齡層</label>
                        <select id="age_range" name="age_range">
                            <option value="">未設定</option>
                            @foreach ($profileOptions['age_ranges'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('age_range', $profile?->age_range) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('age_range')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <label for="photos">作品圖 URL（逗號分隔）</label>
                    <textarea id="photos" name="photos" placeholder="https://...">{{ old('photos', implode('，', $profile?->photos ?? [])) }}</textarea>
                    <fieldset class="choice-group">
                        <legend>參考合作報價 <span class="field-hint">單選</span></legend>
                        <p class="field-help">選擇一般單次合作嘅參考範圍，實際價錢仍可按內容商議。</p>
                        <div class="choice-cards choice-cards-2">
                            @foreach ($profileOptions['rate_ranges'] as $value => $range)
                                <label class="choice-card"><input type="radio" name="rate_range" value="{{ $value }}" @checked($selectedRateRange === $value)><span>{{ $range['label'] }}</span></label>
                            @endforeach
                            @if ($hasExistingRate && $matchedRateRange === false)
                                <label class="choice-card"><input type="radio" name="rate_range" value="existing" @checked($selectedRateRange === 'existing')><span>保留現有：HK${{ number_format((int) ($profile->rate_min ?? 0)) }}–{{ number_format((int) ($profile->rate_max ?? 0)) }}</span></label>
                            @endif
                        </div>
                        @error('rate_range')<p class="field-error">{{ $message }}</p>@enderror
                    </fieldset>
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
                    <div><span>步驟 2 / 5</span><h3>設計你嘅卡片</h3></div>
                    <p>揀模板、背景同色彩；右邊會即時顯示實際卡片效果。</p>
                </div>
                <div class="card-design-workspace">
                <div class="card-design-controls">
                <form method="POST" action="{{ route('kol-card.update') }}" enctype="multipart/form-data" data-card-design-form data-card-fallback="{{ $profile?->bio }}">
                    @csrf
                    @method('PUT')
                    <label for="slug">公開短網址</label>
                    <div class="slug-field {{ $profile?->isSlugLocked() ? 'is-locked' : '' }}">
                        <span>{{ url('/k') }}/</span>
                        <input id="slug" name="slug" value="{{ old('slug', $profile?->slug ?: \Illuminate\Support\Str::slug($user->profileDisplayName())) }}" placeholder="mina-daily" required @readonly($profile?->isSlugLocked())>
                    </div>
                    @if ($profile?->isSlugLocked())
                        <div class="slug-lock-note is-locked">
                            <strong>公開網址已鎖定</strong>
                            <span>為保障身份同已分享連結，首次發布後不可自行更改。如有必要，請聯絡 <a href="mailto:{{ config('kold.support_email') }}">KOLD 支援</a>。</span>
                        </div>
                    @else
                        <p class="field-help">只可使用英文字母、數字同連字號，最少 3 個字元。首次正式發布後會鎖定。</p>
                    @endif
                    @error('slug')<p class="field-error">{{ $message }}</p>@enderror
                    <label for="card_headline">一句介紹</label>
                    <input id="card_headline" name="card_headline" maxlength="160" value="{{ old('card_headline', $profile?->card_headline) }}" placeholder="香港美妝與生活創作者">
                    <label for="external_contact_url">合作聯絡連結</label>
                    <input id="external_contact_url" name="external_contact_url" type="url" value="{{ old('external_contact_url', $profile?->external_contact_url) }}" placeholder="https://wa.me/...">
                    <fieldset class="card-design-fieldset">
                        <legend>卡片模板</legend>
                        <p class="field-help">可隨時更改樣式，唔會影響已鎖定嘅公開網址。</p>
                        <div class="card-template-options">
                            @foreach (config('kold.card_themes') as $value => $option)
                                <label class="card-template-choice">
                                    <input type="radio" name="card_theme" value="{{ $value }}" @checked(old('card_theme', $profile?->card_theme ?? 'classic') === $value)>
                                    <span class="card-template-swatch is-{{ $value }}" aria-hidden="true"><span></span><span></span><span></span></span>
                                    <strong>{{ $option['label'] }}</strong>
                                    <small>{{ $option['description'] }}</small>
                                </label>
                            @endforeach
                        </div>
                        @error('card_theme')<p class="field-error">{{ $message }}</p>@enderror
                    </fieldset>
                    <fieldset class="card-design-fieldset">
                        <legend>重點顏色</legend>
                        <div class="card-accent-options">
                            @foreach (config('kold.card_accents') as $value => $option)
                                <label class="card-accent-choice">
                                    <input type="radio" name="card_accent" value="{{ $value }}" @checked(old('card_accent', $profile?->card_accent ?? 'moss') === $value)>
                                    <span class="card-accent-dot is-{{ $value }}" aria-hidden="true"></span>
                                    {{ $option['label'] }}
                                </label>
                            @endforeach
                        </div>
                        @error('card_accent')<p class="field-error">{{ $message }}</p>@enderror
                    </fieldset>
                    <div class="card-background-editor">
                        <div><strong>背景圖片</strong><p>上載直向相片，卡片會自動加深遮罩，令文字保持清晰。</p></div>
                        <label for="card_background">選擇 JPG、PNG 或 WebP（最多 6MB）</label>
                        <input id="card_background" type="file" name="background" accept="image/jpeg,image/png,image/webp">
                        @error('background')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="builder-actions">
                        <a class="btn btn-ghost" href="{{ route('profile.edit', ['step' => 'profile']) }}">上一步</a>
                        <button class="btn btn-primary" type="submit">儲存並繼續</button>
                    </div>
                </form>
                @if ($profile?->card_background_path)
                    <form method="POST" action="{{ route('kol-card.background.destroy') }}" class="card-background-remove">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-ghost" type="submit">移除背景</button>
                    </form>
                @endif
                </div>
                @include('profile.partials.card-preview', ['previewHint' => '改動會即時顯示；按「儲存並繼續」先會正式保存。'])
                </div>
            @elseif ($step === 'links')
                <div class="builder-section-heading">
                    <div><span>步驟 3 / 5</span><h3>卡片連結</h3></div>
                    <p>加入 Instagram、YouTube、作品集或其他頁面，為每個連結揀一個 icon。</p>
                </div>
                <div class="card-design-workspace">
                <div class="card-design-controls">
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
                    <label for="link_icon">連結 icon</label>
                    <select id="link_icon" name="icon">
                        @foreach (config('kold.card_link_icons') as $value => $option)
                            <option value="{{ $value }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
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
                                <label>連結 icon</label>
                                <select name="icon">
                                    @foreach (config('kold.card_link_icons') as $value => $option)
                                        <option value="{{ $value }}" @selected(($link->icon ?? 'link') === $value)>{{ $option['label'] }}</option>
                                    @endforeach
                                </select>
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
                </div>
                @include('profile.partials.card-preview', ['previewHint' => '新增或更新連結後，右邊嘅卡片預覽會同步更新。'])
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
                    <div><span>網址狀態</span><strong>{{ $profile?->isSlugLocked() ? '已鎖定' : '首次發布時鎖定' }}</strong></div>
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
                        <form class="publish-card-form" method="POST" action="{{ route('kol-card.publish') }}">
                            @csrf
                            @if (! $profile?->isSlugLocked())
                                <label class="slug-lock-confirmation">
                                    <input type="checkbox" name="confirm_slug_lock" value="1" required>
                                    <span>我確認公開網址 <strong>{{ $profile?->slug ? '/k/'.$profile->slug : '' }}</strong> 首次發布後會鎖定。</span>
                                </label>
                            @endif
                            <button class="btn btn-primary" type="submit" @disabled($issues !== [])>發布卡片</button>
                        </form>
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

@section('scripts')
<script>
(() => {
    const form = document.querySelector('[data-profile-structured-form]');
    if (!form) return;

    form.classList.add('is-enhanced');
    form.querySelectorAll('[data-other-group]').forEach((group) => {
        const toggle = group.querySelector('[data-other-toggle]');
        const field = group.querySelector('[data-other-field]');
        const input = field?.querySelector('input');
        if (!toggle || !field || !input) return;

        const sync = () => {
            field.hidden = !toggle.checked;
            input.required = toggle.checked;
        };

        toggle.addEventListener('change', sync);
        sync();
    });
})();

(() => {
    const form = document.querySelector('[data-card-design-form]');
    const frame = document.querySelector('[data-card-preview-frame]');
    if (!form || !frame) return;

    let backgroundUrl = null;
    const sync = () => {
        frame.contentWindow?.postMessage({
            type: 'kold-card-preview',
            theme: form.querySelector('input[name="card_theme"]:checked')?.value || 'classic',
            accent: form.querySelector('input[name="card_accent"]:checked')?.value || 'moss',
            headline: form.querySelector('[name="card_headline"]')?.value.trim() || form.dataset.cardFallback || '',
            backgroundUrl,
        }, window.location.origin);
    };

    form.addEventListener('input', sync);
    form.addEventListener('change', sync);
    frame.addEventListener('load', sync);

    const fileInput = form.querySelector('input[name="background"]');
    fileInput?.addEventListener('change', () => {
        const file = fileInput.files?.[0];
        if (!file || !file.type.startsWith('image/')) return;
        const reader = new FileReader();
        reader.addEventListener('load', () => {
            backgroundUrl = typeof reader.result === 'string' ? reader.result : null;
            sync();
        });
        reader.readAsDataURL(file);
    });
})();

(() => {
    const url = document.querySelector('#link_url');
    const icon = document.querySelector('#link_icon');
    if (!url || !icon) return;

    url.addEventListener('change', () => {
        if (icon.value !== 'link') return;
        let host;
        try { host = new URL(url.value).hostname.toLowerCase(); } catch { return; }
        const known = {
            'instagram.com': 'instagram', 'youtube.com': 'youtube', 'youtu.be': 'youtube',
            'tiktok.com': 'tiktok', 'facebook.com': 'facebook', 'fb.com': 'facebook',
            'wa.me': 'whatsapp', 'whatsapp.com': 'whatsapp',
        };
        for (const [domain, value] of Object.entries(known)) {
            if (host === domain || host.endsWith('.' + domain)) {
                icon.value = value;
                return;
            }
        }
    });
})();
</script>
@endsection
