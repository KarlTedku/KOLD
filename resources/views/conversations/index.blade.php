@extends('layouts.app')

@section('title', '對話 — KOLD')

@section('content')
<section class="section">
    <h2>對話</h2>
    <div class="panel">
        @forelse ($conversations as $conversation)
            @php($other = $conversation->otherParty(auth()->user()))
            <div class="profile-row">
                <h3><a href="{{ route('conversations.show', $conversation) }}">{{ $other->profileDisplayName() }}</a></h3>
                <p style="opacity:.75">{{ $conversation->messages->first()?->body }}</p>
            </div>
        @empty
            <p>接受聯絡請求後，對話會出現在這裡。</p>
        @endforelse
    </div>
</section>
@endsection
