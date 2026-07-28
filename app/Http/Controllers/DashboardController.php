<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user()->load(['kolProfile', 'brandProfile', 'socialAccounts']);

        $pendingInbox = $user->receivedContactRequests()->where('status', 'pending')->count();

        return view('dashboard', compact('user', 'pendingInbox'));
    }
}
