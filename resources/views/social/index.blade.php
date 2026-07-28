@extends('layouts.app')

@section('title', '社群連結 — KOLD')

@section('content')
<section class="section">
    <h2>社群連結</h2>
    <p class="lead">可手動填寫，或在設定 OAuth 後連結 YouTube／Meta。</p>

    <div class="grid grid-2">
        <div class="panel">
            <h3 style="margin-top:0;font-family:var(--font-display)">手動新增</h3>
            <form method="POST" action="{{ route('social.manual') }}">
                @csrf
                <label>平台</label>
                <select name="platform">
                    <option value="instagram">Instagram</option>
                    <option value="youtube">YouTube</option>
                    <option value="facebook">Facebook</option>
                </select>
                <label>Handle</label>
                <input name="handle" required placeholder="@yourhandle">
                <label>粉絲數</label>
                <input type="number" name="follower_count" min="0" value="0">
                <button class="btn btn-primary" type="submit">儲存</button>
            </form>
            <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-top:1rem">
                <a class="btn btn-ghost" href="{{ route('social.connect', 'youtube') }}">OAuth 連 YouTube</a>
                <a class="btn btn-ghost" href="{{ route('social.connect', 'facebook') }}">OAuth 連 Meta／IG</a>
            </div>
        </div>

        <div class="panel">
            <h3 style="margin-top:0;font-family:var(--font-display)">已連結</h3>
            @forelse ($accounts as $account)
                <div class="profile-row">
                    <strong>{{ $account->platform }}</strong>
                    <div>@{{ $account->handle }} · {{ number_format((int) $account->follower_count) }}</div>
                    <form method="POST" action="{{ route('social.destroy', $account->platform) }}">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-ghost" type="submit">解除</button>
                    </form>
                </div>
            @empty
                <p>尚未連結任何社群。</p>
            @endforelse
        </div>
    </div>
</section>
@endsection
