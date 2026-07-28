@extends('layouts.app')

@section('title', '我的檔案 — KOLD')

@section('content')
<section class="section">
    <h2>我的檔案</h2>
    <p class="lead">手動填寫、連結社群，或讓 AI 先產出草稿再微調。</p>

    <div class="grid grid-2">
        <div class="panel">
            @if ($user->isKol())
                @php($profile = $user->kolProfile)
                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf
                    @method('PUT')
                    <label>顯示名稱</label>
                    <input name="display_name" value="{{ old('display_name', $profile?->display_name) }}" required>
                    <label>簡介</label>
                    <textarea name="bio">{{ old('bio', $profile?->bio) }}</textarea>
                    <label>Niches（逗號分隔）</label>
                    <input name="niches" value="{{ old('niches', implode('，', $profile?->niches ?? [])) }}">
                    <label>地區</label>
                    <input name="regions" value="{{ old('regions', implode('，', $profile?->regions ?? [])) }}">
                    <label>語言</label>
                    <input name="languages" value="{{ old('languages', implode('，', $profile?->languages ?? [])) }}">
                    <div class="grid grid-2">
                        <div>
                            <label>報價下限</label>
                            <input type="number" name="rate_min" value="{{ old('rate_min', $profile?->rate_min) }}">
                        </div>
                        <div>
                            <label>報價上限</label>
                            <input type="number" name="rate_max" value="{{ old('rate_max', $profile?->rate_max) }}">
                        </div>
                    </div>
                    <label>狀態</label>
                    <select name="status">
                        <option value="draft" @selected(old('status', $profile?->status) === 'draft')>草稿</option>
                        <option value="published" @selected(old('status', $profile?->status) === 'published')>發佈</option>
                    </select>
                    <button class="btn btn-primary" type="submit">儲存檔案</button>
                </form>
            @else
                @php($profile = $user->brandProfile)
                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf
                    @method('PUT')
                    <label>公司／品牌名稱</label>
                    <input name="company_name" value="{{ old('company_name', $profile?->company_name) }}" required>
                    <label>簡介</label>
                    <textarea name="bio">{{ old('bio', $profile?->bio) }}</textarea>
                    <label>產業（逗號分隔）</label>
                    <input name="industries" value="{{ old('industries', implode('，', $profile?->industries ?? [])) }}">
                    <label>地區</label>
                    <input name="regions" value="{{ old('regions', implode('，', $profile?->regions ?? [])) }}">
                    <label>預算區間</label>
                    <input name="budget_range" value="{{ old('budget_range', $profile?->budget_range) }}">
                    <label>狀態</label>
                    <select name="status">
                        <option value="draft" @selected(old('status', $profile?->status) === 'draft')>草稿</option>
                        <option value="published" @selected(old('status', $profile?->status) === 'published')>發佈</option>
                    </select>
                    <button class="btn btn-primary" type="submit">儲存檔案</button>
                </form>
            @endif
        </div>

        <div>
            <div class="panel" style="margin-bottom:1rem">
                <h3 style="margin-top:0;font-family:var(--font-display)">AI 產生草稿</h3>
                <form method="POST" action="{{ route('profile.ai') }}">
                    @csrf
                    <label>補充說明（可選）</label>
                    <textarea name="notes" placeholder="例如：主打香港美妝、喜歡真實測評"></textarea>
                    <button class="btn btn-accent" type="submit">產生／改寫檔案</button>
                </form>
                @if (session('ai_draft'))
                    <p style="margin-top:1rem;opacity:.8">語調：{{ session('ai_draft')['tone'] ?? '' }}</p>
                @endif
            </div>
            <div class="panel">
                <h3 style="margin-top:0;font-family:var(--font-display)">社群帳號</h3>
                <p><a class="btn btn-soft" href="{{ route('social.index') }}">管理連結</a></p>
                @forelse ($user->socialAccounts as $account)
                    <div class="meta" style="margin-top:.5rem">
                        <span class="chip">{{ $account->platform }}</span>
                        <span>@{{ $account->handle }} · {{ number_format((int) $account->follower_count) }}</span>
                    </div>
                @empty
                    <p style="opacity:.7">尚未連結社群，可手動填寫粉專數據。</p>
                @endforelse
            </div>
        </div>
    </div>
</section>
@endsection
