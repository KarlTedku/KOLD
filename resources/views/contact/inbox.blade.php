@extends('layouts.app')

@section('title', '收件匣 — KOLD')

@section('content')
<section class="section">
    <h2>收件匣</h2>
    <p class="lead">處理收到的合作邀請，並查看你送出的請求。</p>

    <div class="grid grid-2">
        <div class="panel">
            <h3 style="margin-top:0;font-family:var(--font-display)">收到的邀請</h3>
            @forelse ($incoming as $item)
                <div class="profile-row">
                    <strong>{{ $item->fromUser->profileDisplayName() }}</strong>
                    <p style="opacity:.8">{{ $item->message }}</p>
                    <div class="meta">
                        <span class="chip">{{ $item->status }}</span>
                    </div>
                    @if ($item->isPending())
                        <div style="display:flex;gap:.5rem;margin-top:.6rem">
                            <form method="POST" action="{{ route('contact.accept', $item) }}">@csrf<button class="btn btn-primary" type="submit">接受</button></form>
                            <form method="POST" action="{{ route('contact.decline', $item) }}">@csrf<button class="btn btn-ghost" type="submit">拒絕</button></form>
                        </div>
                    @elseif ($item->status === 'accepted' && $item->conversation)
                        <a class="btn btn-soft" href="{{ route('conversations.show', $item->conversation) }}">開啟對話</a>
                    @endif
                </div>
            @empty
                <p>目前沒有收到邀請。</p>
            @endforelse
        </div>

        <div class="panel">
            <h3 style="margin-top:0;font-family:var(--font-display)">已送出</h3>
            @forelse ($outgoing as $item)
                <div class="profile-row">
                    <strong>{{ $item->toUser->profileDisplayName() }}</strong>
                    <p style="opacity:.8">{{ $item->message }}</p>
                    <span class="chip">{{ $item->status }}</span>
                </div>
            @empty
                <p>尚未送出任何邀請。</p>
            @endforelse
        </div>
    </div>
</section>
@endsection
