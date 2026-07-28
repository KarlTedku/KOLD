@extends('layouts.app')

@section('title', '與 '.$other->profileDisplayName().' 的對話 — KOLD')

@section('content')
<section class="section">
    <h2>與 {{ $other->profileDisplayName() }}</h2>
    <p class="lead"><a href="{{ route('discover.show', $other) }}">查看對方檔案</a></p>

    <div class="panel msg-list" style="margin-bottom:1rem">
        @foreach ($conversation->messages as $message)
            <div class="msg {{ $message->user_id === auth()->id() ? 'mine' : '' }}">
                <div style="font-size:.8rem;opacity:.65;margin-bottom:.25rem">{{ $message->user->profileDisplayName() }} · {{ $message->created_at->format('m/d H:i') }}</div>
                {{ $message->body }}
            </div>
        @endforeach
    </div>

    <form class="panel" method="POST" action="{{ route('conversations.messages.store', $conversation) }}">
        @csrf
        <label>回覆</label>
        <textarea name="body" required></textarea>
        <button class="btn btn-primary" type="submit">送出</button>
    </form>
</section>
@endsection
