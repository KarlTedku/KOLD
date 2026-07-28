<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConversationController extends Controller
{
    public function index(): View
    {
        $userId = auth()->id();

        $conversations = Conversation::query()
            ->with(['userOne', 'userTwo', 'messages' => fn ($q) => $q->latest()->limit(1)])
            ->where(function ($q) use ($userId) {
                $q->where('user_one_id', $userId)->orWhere('user_two_id', $userId);
            })
            ->latest()
            ->get();

        return view('conversations.index', compact('conversations'));
    }

    public function show(Conversation $conversation): View
    {
        $this->authorizeParticipant($conversation);

        $conversation->load(['messages.user', 'userOne', 'userTwo']);

        return view('conversations.show', [
            'conversation' => $conversation,
            'other' => $conversation->otherParty(auth()->user()),
        ]);
    }

    public function storeMessage(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorizeParticipant($conversation);

        $data = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:5000'],
        ]);

        Message::query()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        return back();
    }

    protected function authorizeParticipant(Conversation $conversation): void
    {
        $userId = auth()->id();
        abort_unless(in_array($userId, [$conversation->user_one_id, $conversation->user_two_id], true), 403);
    }
}
