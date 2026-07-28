<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function role(): View|RedirectResponse
    {
        if (auth()->user()->hasRole()) {
            return redirect()->route('dashboard');
        }

        return view('onboarding.role');
    }

    public function storeRole(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', 'in:kol,brand'],
        ]);

        $user = $request->user();
        $user->role = $data['role'];
        $user->save();

        if ($user->isKol() && ! $user->kolProfile) {
            $user->kolProfile()->create([
                'display_name' => $user->name,
                'status' => 'draft',
            ]);
        }

        if ($user->isBrand() && ! $user->brandProfile) {
            $user->brandProfile()->create([
                'company_name' => $user->name,
                'status' => 'draft',
            ]);
        }

        return redirect()->route('profile.edit')->with('status', '角色已設定，開始完善你的檔案吧。');
    }
}
