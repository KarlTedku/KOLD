<?php

namespace App\Http\Controllers;

use App\Models\Collaboration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CollaborationController extends Controller
{
    public function update(Request $request, Collaboration $collaboration): RedirectResponse
    {
        abort_unless(in_array($request->user()->id, [$collaboration->brand_user_id, $collaboration->kol_user_id], true), 403);
        $data = $request->validate(['status' => ['required', Rule::in(Collaboration::STATUSES)]]);

        $current = array_search($collaboration->status, Collaboration::STATUSES, true);
        $next = array_search($data['status'], Collaboration::STATUSES, true);
        abort_unless($next === $current + 1, 422);

        $collaboration->update([
            'status' => $data['status'],
            'updated_by_user_id' => $request->user()->id,
        ]);

        return back()->with('status', '合作狀態已更新。');
    }
}
