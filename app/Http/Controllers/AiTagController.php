<?php

namespace App\Http\Controllers;

use App\Models\KolAiTag;
use App\Models\KolProfile;
use App\Services\KolTaggingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AiTagController extends Controller
{
    public function generate(Request $request, KolTaggingService $tagging): RedirectResponse
    {
        $profile = $this->currentKolProfile($request);
        $count = $tagging->generateSuggestedTags($profile)->count();

        return $this->redirectWithStatus("AI 已產生 {$count} 個建議標籤，請確認後再用於配對。");
    }

    public function approve(Request $request, KolAiTag $kolAiTag): RedirectResponse
    {
        $this->authorizeTag($request, $kolAiTag);
        $kolAiTag->update(['status' => 'approved']);

        return $this->redirectWithStatus('標籤已確認。');
    }

    public function approveAll(Request $request): RedirectResponse
    {
        $profile = $this->currentKolProfile($request);
        $count = $profile->aiTags()->where('status', 'suggested')->update(['status' => 'approved']);

        return $this->redirectWithStatus("已確認 {$count} 個 AI 標籤。");
    }

    public function reject(Request $request, KolAiTag $kolAiTag): RedirectResponse
    {
        $this->authorizeTag($request, $kolAiTag);
        $kolAiTag->delete();

        return $this->redirectWithStatus('標籤已移除。');
    }

    protected function currentKolProfile(Request $request): KolProfile
    {
        abort_unless($request->user()->isKol(), 403);

        return $request->user()->kolProfile()->firstOrCreate(
            ['user_id' => $request->user()->id],
            ['display_name' => $request->user()->name, 'status' => 'draft']
        );
    }

    protected function authorizeTag(Request $request, KolAiTag $kolAiTag): void
    {
        abort_unless($request->user()->isKol(), 403);
        abort_unless($kolAiTag->kolProfile?->user_id === $request->user()->id, 403);
    }

    protected function redirectWithStatus(string $status): RedirectResponse
    {
        return redirect()->route('profile.edit', ['step' => 'tags'])->with('status', $status);
    }
}
