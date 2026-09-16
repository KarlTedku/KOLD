@extends('layouts.app')

@section('title', '選擇 Meta／IG 帳號 — KOLD')

@section('content')
<section class="section">
    <h2>選擇要連結的 Instagram 帳號</h2>
    <p class="lead">以下帳號來自你管理的 Facebook Page。請選擇要加入 KOLD 的 Instagram Business／Creator 帳號；主要帳號嘅專業頭像亦會自動帶入，除非你已經手動上載過頭像。</p>

    <form class="panel" method="POST" action="{{ route('social.meta.store') }}">
        @csrf

        @foreach ($candidates as $index => $candidate)
            @php($instagram = $candidate['instagram'])
            <label class="profile-row" style="display:grid;grid-template-columns:auto auto 1fr;gap:.8rem;align-items:start;cursor:pointer">
                <input
                    type="checkbox"
                    name="accounts[]"
                    value="{{ $candidate['key'] }}"
                    checked
                    style="margin-top:.35rem"
                >
                @if ($instagram['profile_picture_url'] ?? null)
                    <img src="{{ $instagram['profile_picture_url'] }}" alt="" style="width:52px;height:52px;border-radius:50%;object-fit:cover">
                @endif
                <span>
                    <strong>{{ '@'.($instagram['username'] ?: $instagram['name']) }}</strong>
                    <span style="display:block;opacity:.78">
                        {{ number_format((int) ($instagram['followers_count'] ?? 0)) }} 粉絲
                        · Page：{{ $candidate['page_name'] }}
                    </span>
                    <span style="display:block;margin-top:.45rem">
                        <input
                            type="radio"
                            name="primary"
                            value="{{ $candidate['key'] }}"
                            @checked($index === 0)
                        >
                        設為主要 Instagram
                    </span>
                </span>
            </label>
        @endforeach

        <div style="display:flex;gap:.75rem;flex-wrap:wrap;margin-top:1.25rem">
            <button class="btn btn-primary" type="submit">連結已選帳號</button>
            <a class="btn btn-ghost" href="{{ route('social.index') }}">取消</a>
        </div>
    </form>
</section>
@endsection
