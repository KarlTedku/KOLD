<?php

namespace App\Http\Controllers;

use App\Services\BrandBriefMatchService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandMatchController extends Controller
{
    public function __invoke(Request $request, BrandBriefMatchService $matcher): View
    {
        abort_unless($request->user()->isBrand(), 403);

        $form = $request->validate([
            'product' => ['nullable', 'string', 'max:300'],
            'audience' => ['nullable', 'string', 'max:300'],
            'region' => ['nullable', 'string', 'max:120'],
            'platform' => ['nullable', 'string', 'max:120'],
            'collaboration' => ['nullable', 'string', 'max:200'],
            'budget_min' => ['nullable', 'integer', 'min:0'],
            'budget_max' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1200'],
            'brief' => ['nullable', 'string', 'min:5', 'max:2000'],
        ]);

        if (filled($form['budget_min'] ?? null) && filled($form['budget_max'] ?? null) && (int) $form['budget_max'] < (int) $form['budget_min']) {
            return back()->withErrors(['budget_max' => '預算上限必須大於或等於預算下限。'])->withInput();
        }

        if (filled($form['brief'] ?? null) && blank($form['notes'] ?? null)) {
            $form['notes'] = $form['brief'];
        }

        $match = null;

        if (collect($form)->except('brief')->filter(fn ($value) => filled($value))->isNotEmpty()) {
            $match = $matcher->match($form);
        }

        return view('match.index', compact('form', 'match'));
    }
}
