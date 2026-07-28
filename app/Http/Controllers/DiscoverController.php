<?php

namespace App\Http\Controllers;

use App\Models\BrandProfile;
use App\Models\KolProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiscoverController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $q = trim((string) $request->get('q', ''));
        $region = trim((string) $request->get('region', ''));
        $niche = trim((string) $request->get('niche', ''));

        if ($user->isBrand()) {
            $profiles = KolProfile::query()
                ->with(['user.socialAccounts'])
                ->where('status', 'published')
                ->when($q !== '', function ($query) use ($q) {
                    $query->where(function ($inner) use ($q) {
                        $inner->where('display_name', 'like', "%{$q}%")
                            ->orWhere('bio', 'like', "%{$q}%");
                    });
                })
                ->when($niche !== '', function ($query) use ($niche) {
                    $query->where('niches', 'like', "%{$niche}%");
                })
                ->when($region !== '', function ($query) use ($region) {
                    $query->where('regions', 'like', "%{$region}%");
                })
                ->latest()
                ->paginate(12)
                ->withQueryString();

            $mode = 'kol';
        } else {
            $profiles = BrandProfile::query()
                ->with(['user.socialAccounts'])
                ->where('status', 'published')
                ->when($q !== '', function ($query) use ($q) {
                    $query->where(function ($inner) use ($q) {
                        $inner->where('company_name', 'like', "%{$q}%")
                            ->orWhere('bio', 'like', "%{$q}%");
                    });
                })
                ->when($niche !== '', function ($query) use ($niche) {
                    $query->where('industries', 'like', "%{$niche}%");
                })
                ->when($region !== '', function ($query) use ($region) {
                    $query->where('regions', 'like', "%{$region}%");
                })
                ->latest()
                ->paginate(12)
                ->withQueryString();

            $mode = 'brand';
        }

        return view('discover.index', compact('profiles', 'mode', 'q', 'region', 'niche'));
    }

    public function show(User $user): View
    {
        abort_unless($user->isPublished(), 404);

        $user->load(['kolProfile', 'brandProfile', 'socialAccounts']);

        $existingRequest = null;
        if (auth()->check() && auth()->id() !== $user->id) {
            $existingRequest = auth()->user()
                ->sentContactRequests()
                ->with('conversation')
                ->where('to_user_id', $user->id)
                ->latest()
                ->first();
        }

        return view('discover.show', compact('user', 'existingRequest'));
    }
}
