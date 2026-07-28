<?php

namespace App\Http\Controllers;

use App\Models\ContactRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function inbox(): View
    {
        $user = auth()->user();

        $incoming = $user->receivedContactRequests()
            ->with(['fromUser.kolProfile', 'fromUser.brandProfile', 'conversation'])
            ->latest()
            ->get();

        $outgoing = $user->sentContactRequests()
            ->with(['toUser.kolProfile', 'toUser.brandProfile', 'conversation'])
            ->latest()
            ->get();

        return view('contact.inbox', compact('incoming', 'outgoing'));
    }

    public function store(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->id === $user->id, 403);
        abort_unless($user->isPublished(), 404);

        $data = $request->validate([
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $exists = ContactRequest::query()
            ->where('from_user_id', $request->user()->id)
            ->where('to_user_id', $user->id)
            ->where('status', 'pending')
            ->exists();

        if ($exists) {
            return back()->with('error', '你已有一則待處理的聯絡請求。');
        }

        ContactRequest::query()->create([
            'from_user_id' => $request->user()->id,
            'to_user_id' => $user->id,
            'message' => $data['message'],
            'status' => 'pending',
        ]);

        return redirect()->route('contact.inbox')->with('status', '聯絡請求已送出。');
    }

    public function accept(ContactRequest $contactRequest): RedirectResponse
    {
        $this->authorizeRecipient($contactRequest);
        abort_unless($contactRequest->isPending(), 422);

        $contactRequest->update([
            'status' => 'accepted',
            'responded_at' => now(),
        ]);

        $conversation = Conversation::query()->firstOrCreate(
            ['contact_request_id' => $contactRequest->id],
            [
                'user_one_id' => $contactRequest->from_user_id,
                'user_two_id' => $contactRequest->to_user_id,
            ]
        );

        Message::query()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $contactRequest->from_user_id,
            'body' => $contactRequest->message,
        ]);

        return redirect()->route('conversations.show', $conversation)->with('status', '已接受，可以開始對話。');
    }

    public function decline(ContactRequest $contactRequest): RedirectResponse
    {
        $this->authorizeRecipient($contactRequest);
        abort_unless($contactRequest->isPending(), 422);

        $contactRequest->update([
            'status' => 'declined',
            'responded_at' => now(),
        ]);

        return back()->with('status', '已拒絕此聯絡請求。');
    }

    protected function authorizeRecipient(ContactRequest $contactRequest): void
    {
        abort_unless($contactRequest->to_user_id === auth()->id(), 403);
    }
}
