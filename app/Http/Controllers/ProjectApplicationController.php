<?php

namespace App\Http\Controllers;

use App\Models\BrandProject;
use App\Models\Collaboration;
use App\Models\ContactRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\ProjectApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProjectApplicationController extends Controller
{
    public function store(Request $request, BrandProject $project): RedirectResponse
    {
        abort_unless($request->user()->isKol(), 403);
        abort_unless($project->isOpen(), 422);

        $data = $request->validate([
            'pitch' => ['required', 'string', 'max:3000'],
            'proposed_rate' => ['nullable', 'integer', 'min:0'],
        ]);

        if ($project->applications()->where('kol_user_id', $request->user()->id)->exists()) {
            return back()->with('error', '你已經申請過呢個 Project。');
        }

        $project->applications()->create([
            'kol_user_id' => $request->user()->id,
            'pitch' => $data['pitch'],
            'proposed_rate' => $data['proposed_rate'] ?? null,
            'status' => 'pending',
        ]);

        return back()->with('status', '申請已送出。');
    }

    public function index(Request $request, BrandProject $project): View
    {
        $this->authorizeOwner($request, $project);
        $applications = $project->applications()->with(['kol.kolProfile', 'collaboration.conversation'])->latest()->get();

        return view('projects.applications', compact('project', 'applications'));
    }

    public function accept(Request $request, ProjectApplication $application): RedirectResponse
    {
        $application->load('project');
        $this->authorizeOwner($request, $application->project);
        abort_unless($application->status === 'pending', 422);

        $conversation = DB::transaction(function () use ($application): Conversation {
            $contactRequest = ContactRequest::query()->create([
                'from_user_id' => $application->kol_user_id,
                'to_user_id' => $application->project->brand_user_id,
                'message' => $application->pitch,
                'status' => 'accepted',
                'responded_at' => now(),
            ]);
            $conversation = Conversation::query()->create([
                'contact_request_id' => $contactRequest->id,
                'user_one_id' => $application->kol_user_id,
                'user_two_id' => $application->project->brand_user_id,
            ]);
            Message::query()->create([
                'conversation_id' => $conversation->id,
                'user_id' => $application->kol_user_id,
                'body' => $application->pitch,
            ]);
            $application->update([
                'status' => 'accepted',
                'responded_at' => now(),
                'contact_request_id' => $contactRequest->id,
            ]);
            Collaboration::query()->create([
                'brand_user_id' => $application->project->brand_user_id,
                'kol_user_id' => $application->kol_user_id,
                'brand_project_id' => $application->brand_project_id,
                'project_application_id' => $application->id,
                'contact_request_id' => $contactRequest->id,
                'conversation_id' => $conversation->id,
                'updated_by_user_id' => auth()->id(),
                'title' => $application->project->title,
                'status' => 'negotiating',
            ]);

            return $conversation;
        });

        return redirect()->route('conversations.show', $conversation)->with('status', '已接受申請，可以開始洽談。');
    }

    public function decline(Request $request, ProjectApplication $application): RedirectResponse
    {
        $application->load('project');
        $this->authorizeOwner($request, $application->project);
        abort_unless($application->status === 'pending', 422);
        $application->update(['status' => 'declined', 'responded_at' => now()]);

        return back()->with('status', '已拒絕申請。');
    }

    public function withdraw(Request $request, ProjectApplication $application): RedirectResponse
    {
        abort_unless($application->kol_user_id === $request->user()->id, 403);
        abort_unless($application->status === 'pending', 422);
        $application->update(['status' => 'withdrawn', 'responded_at' => now()]);

        return back()->with('status', '申請已撤回。');
    }

    protected function authorizeOwner(Request $request, BrandProject $project): void
    {
        abort_unless($request->user()->isBrand() && $project->brand_user_id === $request->user()->id, 403);
    }
}
