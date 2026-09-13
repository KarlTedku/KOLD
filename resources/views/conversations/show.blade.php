@extends('layouts.app')

@section('title', '與 '.$other->profileDisplayName().' 的對話 — KOLD')

@section('content')
<section class="section">
    <h2>與 {{ $other->profileDisplayName() }}</h2>
    <p class="lead"><a href="{{ route('discover.show', $other) }}">查看對方檔案</a></p>

    @if ($conversation->collaboration)
        @php
            $collaboration = $conversation->collaboration;
            $currentIndex = array_search($collaboration->status, \App\Models\Collaboration::STATUSES, true);
            $nextStatus = \App\Models\Collaboration::STATUSES[$currentIndex + 1] ?? null;
            $labels = ['negotiating' => '洽談中', 'confirmed' => '已確認', 'in_progress' => '進行中', 'completed' => '已完成'];
        @endphp
        <section class="collaboration-panel">
            <div class="collaboration-heading">
                <div>
                    <span class="eyebrow">Collaboration</span>
                    <h3>{{ $collaboration->title ?: '合作進度' }}</h3>
                    @if ($collaboration->project)<a href="{{ route('projects.show', $collaboration->project) }}">查看 Project</a>@endif
                </div>
                @if ($nextStatus)
                    <form method="POST" action="{{ route('collaborations.update', $collaboration) }}">
                        @csrf @method('PUT')
                        <input type="hidden" name="status" value="{{ $nextStatus }}">
                        <button class="btn btn-primary" type="submit">更新為{{ $labels[$nextStatus] }}</button>
                    </form>
                @endif
            </div>
            <ol class="collaboration-steps">
                @foreach (\App\Models\Collaboration::STATUSES as $index => $status)
                    <li class="{{ $index <= $currentIndex ? 'is-complete' : '' }} {{ $status === $collaboration->status ? 'is-current' : '' }}">
                        <span>{{ $index + 1 }}</span><strong>{{ $labels[$status] }}</strong>
                    </li>
                @endforeach
            </ol>
        </section>
    @endif

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
