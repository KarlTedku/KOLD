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
        $ageRange = trim((string) $request->get('age_range', ''));
        $followers = trim((string) $request->get('followers', ''));
        $platform = trim((string) $request->get('platform', ''));

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
                ->when($ageRange !== '', function ($query) use ($ageRange) {
                    $query->where('age_range', $ageRange);
                })
                ->when($platform !== '', function ($query) use ($platform) {
                    $query->whereHas('user.socialAccounts', function ($social) use ($platform) {
                        $social->where('platform', $platform);
                    });
                })
                ->when($followers !== '', function ($query) use ($followers) {
                    [$min, $max] = $this->followerBounds($followers);
                    $query->whereHas('user', function ($userQuery) use ($min, $max) {
                        $userQuery->whereRaw(
                            '(select coalesce(sum(follower_count), 0) from social_accounts where social_accounts.user_id = users.id) >= ?',
                            [$min]
                        );
                        if ($max !== null) {
                            $userQuery->whereRaw(
                                '(select coalesce(sum(follower_count), 0) from social_accounts where social_accounts.user_id = users.id) <= ?',
                                [$max]
                            );
                        }
                    });
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
            $ageRange = '';
            $followers = '';
            $platform = '';
        }

        return view('discover.index', compact(
            'profiles',
            'mode',
            'q',
            'region',
            'niche',
            'ageRange',
            'followers',
            'platform'
        ));
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

    /**
     * @return array{0: int, 1: int|null}
     */
    protected function followerBounds(string $bucket): array
    {
        return match ($bucket) {
            'under_10k' => [0, 9999],
            '10_50k' => [10000, 49999],
            '50_200k' => [50000, 199999],
            '200k_plus' => [200000, null],
            default => [0, null],
        };
    }
}
