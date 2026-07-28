<?php

namespace Database\Seeders;

use App\Models\BrandProfile;
use App\Models\KolProfile;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $kol = User::query()->updateOrCreate(
            ['email' => 'kol.demo@kold.local'],
            [
                'name' => '示範 KOL',
                'role' => 'kol',
                'provider' => 'demo',
                'provider_id' => 'demo-kol',
                'email_verified_at' => now(),
            ]
        );

        KolProfile::query()->updateOrCreate(
            ['user_id' => $kol->id],
            [
                'display_name' => 'Mina 日常誌',
                'bio' => '香港生活與美妝創作者，擅長真實測評與短影音腳本。',
                'niches' => ['美妝', '生活', '短影音'],
                'regions' => ['香港'],
                'languages' => ['粵語', '繁中'],
                'rate_min' => 3000,
                'rate_max' => 12000,
                'status' => 'published',
            ]
        );

        SocialAccount::query()->updateOrCreate(
            ['user_id' => $kol->id, 'platform' => 'instagram'],
            [
                'handle' => 'mina.daily',
                'follower_count' => 82000,
                'metrics_json' => ['source' => 'seed'],
                'synced_at' => now(),
            ]
        );

        $kol2 = User::query()->updateOrCreate(
            ['email' => 'kol2@kold.local'],
            [
                'name' => 'Alex 旅拍',
                'role' => 'kol',
                'provider' => 'demo',
                'provider_id' => 'demo-kol-2',
                'email_verified_at' => now(),
            ]
        );

        KolProfile::query()->updateOrCreate(
            ['user_id' => $kol2->id],
            [
                'display_name' => 'Alex Travels',
                'bio' => '亞太旅遊與城市探索，YouTube 長片 + Reels。',
                'niches' => ['旅遊', '攝影'],
                'regions' => ['香港', '台灣'],
                'languages' => ['英文', '繁中'],
                'rate_min' => 8000,
                'rate_max' => 25000,
                'status' => 'published',
            ]
        );

        SocialAccount::query()->updateOrCreate(
            ['user_id' => $kol2->id, 'platform' => 'youtube'],
            [
                'handle' => 'AlexTravels',
                'follower_count' => 145000,
                'metrics_json' => ['source' => 'seed'],
                'synced_at' => now(),
            ]
        );

        $brand = User::query()->updateOrCreate(
            ['email' => 'brand.demo@kold.local'],
            [
                'name' => '示範品牌',
                'role' => 'brand',
                'provider' => 'demo',
                'provider_id' => 'demo-brand',
                'email_verified_at' => now(),
            ]
        );

        BrandProfile::query()->updateOrCreate(
            ['user_id' => $brand->id],
            [
                'company_name' => 'Harbor Skin',
                'bio' => '香港護膚品牌，尋找真實口碑型創作者做新品體驗。',
                'industries' => ['美妝', '護膚'],
                'regions' => ['香港'],
                'budget_range' => 'HKD 5k–20k / 專案',
                'status' => 'published',
            ]
        );
    }
}
