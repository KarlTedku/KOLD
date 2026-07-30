@extends('layouts.app')

@section('title', $user->profileDisplayName().' — KOLD')

@section('content')
<section class="section">
    <div class="profile-hero">
        @if ($user->avatar)
            <img class="profile-hero-avatar" src="{{ $user->avatar }}" alt="{{ $user->profileDisplayName() }}">
        @endif
        <div>
            <h2 style="margin:0">{{ $user->profileDisplayName() }}</h2>
            <p class="lead" style="margin:.4rem 0 0">{{ $user->isKol() ? 'KOL 公開檔案' : '品牌公開檔案' }}</p>
            @if ($user->isKol() && $user->kolProfile?->age_range)
                <div class="meta" style="margin-top:.55rem">
                    <span class="chip">年齡層 {{ $user->kolProfile->age_range }}</span>
                    @foreach ($user->kolProfile->regions ?? [] as $region)
                        <span class="chip">{{ $region }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="grid grid-2" style="margin-top:1.25rem">
        <div class="panel">
            @if ($user->isKol() && $user->kolProfile)
                <p>{{ $user->kolProfile->bio }}</p>
                <div class="meta">
                    @foreach ($user->kolProfile->niches ?? [] as $tag)
                        <span class="chip">{{ $tag }}</span>
                    @endforeach
                </div>
                <p style="margin-top:1rem;opacity:.75">
                    語言：{{ implode('、', $user->kolProfile->languages ?? []) ?: '—' }}
                </p>
                @if ($user->kolProfile->rate_min || $user->kolProfile->rate_max)
                    <p>參考報價：{{ number_format((int) ($user->kolProfile->rate_min ?? 0)) }} – {{ number_format((int) ($user->kolProfile->rate_max ?? 0)) }}</p>
                @endif

                @if (! empty($user->kolProfile->photos))
                    <h3 style="margin:1.4rem 0 .7rem;font-family:var(--font-display)">作品</h3>
                    <div class="photo-wall">
                        @foreach ($user->kolProfile->photos as $photo)
                            <img src="{{ $photo }}" alt="作品">
                        @endforeach
                    </div>
                @endif
            @elseif ($user->isBrand() && $user->brandProfile)
                <p>{{ $user->brandProfile->bio }}</p>
                <div class="meta">
                    @foreach ($user->brandProfile->industries ?? [] as $tag)
                        <span class="chip">{{ $tag }}</span>
                    @endforeach
                </div>
                <p style="margin-top:1rem;opacity:.75">
                    地區：{{ implode('、', $user->brandProfile->regions ?? []) ?: '—' }}
                    · 預算：{{ $user->brandProfile->budget_range ?: '—' }}
                </p>
            @endif

            <div style="margin-top:1.2rem">
                @foreach ($user->socialAccounts as $account)
                    <div class="meta" style="margin-bottom:.4rem">
                        <span class="chip">{{ $account->platform }}</span>
                        <span>@{{ $account->handle }} · {{ number_format((int) $account->follower_count) }} 粉絲</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="panel">
            @auth
                @if (auth()->id() === $user->id)
                    <p>這是你自己的公開頁。</p>
                    <a class="btn btn-soft" href="{{ route('profile.edit') }}">編輯檔案</a>
                @elseif ($existingRequest && $existingRequest->status === 'pending')
                    <p>你已送出聯絡請求，等待對方回覆。</p>
                @elseif ($existingRequest && $existingRequest->status === 'accepted')
                    <p>雙方已建立聯繫。</p>
                    @if ($existingRequest->conversation)
                        <a class="btn btn-primary" href="{{ route('conversations.show', $existingRequest->conversation) }}">前往對話</a>
                    @endif
                @else
                    <h3 style="margin-top:0;font-family:var(--font-display)">送出合作邀請</h3>
                    <form method="POST" action="{{ route('contact.store', $user) }}">
                        @csrf
                        <label>訊息</label>
                        <textarea name="message" required minlength="10" placeholder="簡單說明合作方向、檔期與預算區間"></textarea>
                        <button class="btn btn-accent" type="submit">送出聯絡請求</button>
                    </form>
                @endif
            @else
                <p>登入後可送出聯絡請求。</p>
            @endauth
        </div>
    </div>
</section>
@endsection
