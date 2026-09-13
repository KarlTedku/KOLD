@extends('layouts.app')

@section('title', '社群連結 — KOLD')

@section('content')
<section class="section">
    <h2>社群連結</h2>
    <p class="lead">可手動填寫，或透過 OAuth 連結 YouTube／Meta Page／Instagram Business。</p>

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
            @php
                $sections = [
                    'instagram_business' => 'Instagram Business / Creator',
                    'facebook_page' => 'Facebook Page',
                    'manual' => '手動資料',
                ];
                $hasAccounts = $accounts->flatten(1)->isNotEmpty();
            @endphp

            @if ($hasAccounts)
                @foreach ($sections as $type => $label)
                    @continue(($accounts[$type] ?? collect())->isEmpty())
                    <h4 style="margin:1rem 0 .55rem">{{ $label }}</h4>
                    @foreach ($accounts[$type] as $account)
                        <div class="profile-row">
                            <strong>
                                {{ $account->platform }}
                                @if ($account->is_primary)
                                    <span class="chip">主要</span>
                                @endif
                            </strong>
                            <div>
                                @if ($account->handle)
                                    {{ '@'.$account->handle }}
                                @else
                                    —
                                @endif
                                · {{ number_format((int) $account->follower_count) }} 粉絲
                            </div>
                            @if ($account->page_name)
                                <div style="opacity:.7">Page：{{ $account->page_name }}</div>
                            @endif
                            <div style="opacity:.7">
                                最後同步：{{ $account->synced_at?->format('Y-m-d H:i') ?? '—' }}
                            </div>
                            <form method="POST" action="{{ route('social.destroy', $account) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-ghost" type="submit">解除</button>
                            </form>
                        </div>
                    @endforeach
                @endforeach
            @else
                <p>尚未連結任何社群。</p>
            @endif
        </div>
    </div>
</section>
@endsection
