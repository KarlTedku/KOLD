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
        $this->seedKols();
        $this->seedBrands();
    }

    protected function seedKols(): void
    {
        $kols = [
            [
                'email' => 'kol.demo@kold.local',
                'name' => '示範 KOL',
                'provider_id' => 'demo-kol',
                'display_name' => 'Mina 日常誌',
                'bio' => '香港生活與美妝創作者，擅長真實測評與短影音腳本。',
                'niches' => ['美妝', '生活', '短影音'],
                'regions' => ['香港'],
                'languages' => ['粵語', '繁中'],
                'age_range' => '25-34',
                'rate_min' => 3000,
                'rate_max' => 12000,
                'social' => [['instagram', 'mina.daily', 82000], ['youtube', 'MinaDaily', 21000]],
            ],
            [
                'email' => 'kol2@kold.local',
                'name' => 'Alex 旅拍',
                'provider_id' => 'demo-kol-2',
                'display_name' => 'Alex Travels',
                'bio' => '亞太旅遊與城市探索，YouTube 長片 + Reels。',
                'niches' => ['旅遊', '攝影'],
                'regions' => ['香港', '台灣'],
                'languages' => ['英文', '繁中'],
                'age_range' => '25-34',
                'rate_min' => 8000,
                'rate_max' => 25000,
                'social' => [['youtube', 'AlexTravels', 145000], ['instagram', 'alex.travels', 68000]],
            ],
            [
                'email' => 'kol.yuki@kold.local',
                'name' => 'Yuki',
                'provider_id' => 'seed-kol-yuki',
                'display_name' => 'Yuki Skincare',
                'bio' => '敏感肌護膚日記，主打成分解析與平價替代。',
                'niches' => ['美妝', '護膚'],
                'regions' => ['香港'],
                'languages' => ['粵語', '繁中'],
                'age_range' => '18-24',
                'rate_min' => 2500,
                'rate_max' => 9000,
                'social' => [['instagram', 'yuki.skin', 28000]],
            ],
            [
                'email' => 'kol.ben@kold.local',
                'name' => 'Ben',
                'provider_id' => 'seed-kol-ben',
                'display_name' => 'Ben Eats HK',
                'bio' => '街頭美食探店，短影音節奏快、重口感描述。',
                'niches' => ['美食', '生活'],
                'regions' => ['香港'],
                'languages' => ['粵語'],
                'age_range' => '25-34',
                'rate_min' => 4000,
                'rate_max' => 15000,
                'social' => [['instagram', 'beneatshk', 156000], ['facebook', 'BenEatsHK', 42000]],
            ],
            [
                'email' => 'kol.cara@kold.local',
                'name' => 'Cara',
                'provider_id' => 'seed-kol-cara',
                'display_name' => 'Cara Fit',
                'bio' => '居家健身與體態管理，適合忙碌上班族。',
                'niches' => ['健身', '生活'],
                'regions' => ['香港', '新加坡'],
                'languages' => ['英文', '粵語'],
                'age_range' => '25-34',
                'rate_min' => 5000,
                'rate_max' => 18000,
                'social' => [['instagram', 'cara.fit', 94000], ['youtube', 'CaraFitLab', 51000]],
            ],
            [
                'email' => 'kol.daisy@kold.local',
                'name' => 'Daisy',
                'provider_id' => 'seed-kol-daisy',
                'display_name' => 'Daisy & Little',
                'bio' => '親子日常與嬰幼兒用品真實測評。',
                'niches' => ['親子', '生活'],
                'regions' => ['香港'],
                'languages' => ['粵語', '繁中'],
                'age_range' => '25-34',
                'rate_min' => 3500,
                'rate_max' => 14000,
                'social' => [['instagram', 'daisy.little', 47000], ['facebook', 'DaisyAndLittle', 19000]],
            ],
            [
                'email' => 'kol.eric@kold.local',
                'name' => 'Eric',
                'provider_id' => 'seed-kol-eric',
                'display_name' => 'Eric Gadget',
                'bio' => '消費電子開箱，側重續航、相機與日常場景。',
                'niches' => ['科技', '生活'],
                'regions' => ['香港', '台灣'],
                'languages' => ['粵語', '英文'],
                'age_range' => '25-34',
                'rate_min' => 6000,
                'rate_max' => 30000,
                'social' => [['youtube', 'EricGadget', 220000], ['instagram', 'eric.gadget', 39000]],
            ],
            [
                'email' => 'kol.flora@kold.local',
                'name' => 'Flora',
                'provider_id' => 'seed-kol-flora',
                'display_name' => 'Flora Style',
                'bio' => '通勤穿搭與膠囊衣櫥，重視實穿與價格帶。',
                'niches' => ['時尚', '生活'],
                'regions' => ['香港'],
                'languages' => ['繁中', '粵語'],
                'age_range' => '18-24',
                'rate_min' => 2800,
                'rate_max' => 11000,
                'social' => [['instagram', 'flora.style', 63000]],
            ],
            [
                'email' => 'kol.gigi@kold.local',
                'name' => 'Gigi',
                'provider_id' => 'seed-kol-gigi',
                'display_name' => 'Gigi Cafe Hop',
                'bio' => '咖啡店與甜點打卡，適合品牌季節企劃。',
                'niches' => ['美食', '生活'],
                'regions' => ['香港', '澳門'],
                'languages' => ['粵語'],
                'age_range' => '18-24',
                'rate_min' => 2000,
                'rate_max' => 8000,
                'social' => [['instagram', 'gigi.cafehop', 18000]],
            ],
            [
                'email' => 'kol.hugo@kold.local',
                'name' => 'Hugo',
                'provider_id' => 'seed-kol-hugo',
                'display_name' => 'Hugo Outdoors',
                'bio' => '露營、行山與戶外裝備分享。',
                'niches' => ['旅遊', '戶外'],
                'regions' => ['香港', '台灣'],
                'languages' => ['粵語', '繁中'],
                'age_range' => '35-44',
                'rate_min' => 7000,
                'rate_max' => 22000,
                'social' => [['youtube', 'HugoOutdoors', 88000], ['instagram', 'hugo.outdoors', 41000]],
            ],
            [
                'email' => 'kol.iris@kold.local',
                'name' => 'Iris',
                'provider_id' => 'seed-kol-iris',
                'display_name' => 'Iris Beauty Lab',
                'bio' => '彩妝教學與活動跟妝，擅長亞洲膚色調色。',
                'niches' => ['美妝', '時尚'],
                'regions' => ['香港'],
                'languages' => ['粵語', '英文'],
                'age_range' => '25-34',
                'rate_min' => 4500,
                'rate_max' => 16000,
                'social' => [['instagram', 'iris.beautylab', 112000], ['youtube', 'IrisBeautyLab', 34000]],
            ],
            [
                'email' => 'kol.jay@kold.local',
                'name' => 'Jay',
                'provider_id' => 'seed-kol-jay',
                'display_name' => 'Jay Finance Lite',
                'bio' => '年輕族群理財入門，內容生活化、少術語。',
                'niches' => ['財經', '生活'],
                'regions' => ['香港', '新加坡'],
                'languages' => ['粵語', '英文'],
                'age_range' => '25-34',
                'rate_min' => 5000,
                'rate_max' => 20000,
                'social' => [['youtube', 'JayFinanceLite', 76000], ['instagram', 'jay.finance', 22000]],
            ],
            [
                'email' => 'kol.kate@kold.local',
                'name' => 'Kate',
                'provider_id' => 'seed-kol-kate',
                'display_name' => 'Kate Home',
                'bio' => '小宅收納與家居選物，適合家品品牌。',
                'niches' => ['家居', '生活'],
                'regions' => ['香港'],
                'languages' => ['繁中', '粵語'],
                'age_range' => '35-44',
                'rate_min' => 3000,
                'rate_max' => 12000,
                'social' => [['instagram', 'kate.home', 54000], ['facebook', 'KateHomeHK', 27000]],
            ],
            [
                'email' => 'kol.leo@kold.local',
                'name' => 'Leo',
                'provider_id' => 'seed-kol-leo',
                'display_name' => 'Leo Runs',
                'bio' => '路跑訓練與跑鞋評測，活動出席經驗豐富。',
                'niches' => ['健身', '運動'],
                'regions' => ['香港'],
                'languages' => ['粵語', '英文'],
                'age_range' => '35-44',
                'rate_min' => 4000,
                'rate_max' => 15000,
                'social' => [['instagram', 'leo.runs', 37000], ['youtube', 'LeoRunsHK', 12000]],
            ],
            [
                'email' => 'kol.mia@kold.local',
                'name' => 'Mia',
                'provider_id' => 'seed-kol-mia',
                'display_name' => 'Mia Micro',
                'bio' => '新興美妝創作者，互動高、適合試水溫 campaign。',
                'niches' => ['美妝'],
                'regions' => ['香港'],
                'languages' => ['粵語'],
                'age_range' => '18-24',
                'rate_min' => 1500,
                'rate_max' => 5000,
                'social' => [['instagram', 'mia.micro', 7200]],
            ],
            [
                'email' => 'kol.noah@kold.local',
                'name' => 'Noah',
                'provider_id' => 'seed-kol-noah',
                'display_name' => 'Noah Nomad',
                'bio' => '數位遊牧與長住旅居，內容偏深度長片。',
                'niches' => ['旅遊', '生活'],
                'regions' => ['台灣', '日本'],
                'languages' => ['英文', '繁中'],
                'age_range' => '25-34',
                'rate_min' => 10000,
                'rate_max' => 40000,
                'social' => [['youtube', 'NoahNomad', 310000], ['instagram', 'noah.nomad', 98000]],
            ],
        ];

        foreach ($kols as $index => $data) {
            $user = User::query()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'role' => 'kol',
                    'provider' => 'demo',
                    'provider_id' => $data['provider_id'],
                    'email_verified_at' => now(),
                    'avatar' => $this->avatarUrl($data['provider_id']),
                ]
            );

            $photos = [];
            for ($i = 1; $i <= 4; $i++) {
                $photos[] = $this->photoUrl($data['provider_id'].'-'.$i);
            }

            KolProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'display_name' => $data['display_name'],
                    'bio' => $data['bio'],
                    'niches' => $data['niches'],
                    'regions' => $data['regions'],
                    'languages' => $data['languages'],
                    'age_range' => $data['age_range'],
                    'photos' => $photos,
                    'rate_min' => $data['rate_min'],
                    'rate_max' => $data['rate_max'],
                    'status' => 'published',
                ]
            );

            foreach ($data['social'] as [$platform, $handle, $followers]) {
                SocialAccount::query()->updateOrCreate(
                    ['user_id' => $user->id, 'platform' => $platform],
                    [
                        'handle' => $handle,
                        'follower_count' => $followers,
                        'metrics_json' => ['source' => 'seed'],
                        'synced_at' => now(),
                    ]
                );
            }
        }
    }

    protected function seedBrands(): void
    {
        $brands = [
            [
                'email' => 'brand.demo@kold.local',
                'name' => '示範品牌',
                'provider_id' => 'demo-brand',
                'company_name' => 'Harbor Skin',
                'bio' => '香港護膚品牌，尋找真實口碑型創作者做新品體驗。',
                'industries' => ['美妝', '護膚'],
                'regions' => ['香港'],
                'budget_range' => 'HKD 5k–20k / 專案',
            ],
            [
                'email' => 'brand.trail@kold.local',
                'name' => 'Trail Brew',
                'provider_id' => 'seed-brand-trail',
                'company_name' => 'Trail Brew',
                'bio' => '本地精品咖啡，想找美食／生活創作者做季節聯乘。',
                'industries' => ['美食', '生活'],
                'regions' => ['香港'],
                'budget_range' => 'HKD 3k–12k / 專案',
            ],
            [
                'email' => 'brand.pulse@kold.local',
                'name' => 'Pulse Wear',
                'provider_id' => 'seed-brand-pulse',
                'company_name' => 'Pulse Wear',
                'bio' => '運動服飾新線，需要健身與路跑內容合作。',
                'industries' => ['健身', '時尚'],
                'regions' => ['香港', '新加坡'],
                'budget_range' => 'HKD 8k–25k / 專案',
            ],
            [
                'email' => 'brand.nest@kold.local',
                'name' => 'Nest Baby',
                'provider_id' => 'seed-brand-nest',
                'company_name' => 'Nest Baby',
                'bio' => '嬰幼兒用品，重視真實家長分享多於硬廣。',
                'industries' => ['親子', '家居'],
                'regions' => ['香港'],
                'budget_range' => 'HKD 4k–15k / 專案',
            ],
            [
                'email' => 'brand.orbit@kold.local',
                'name' => 'Orbit Tech',
                'provider_id' => 'seed-brand-orbit',
                'company_name' => 'Orbit Tech',
                'bio' => '消費電子新品，尋找科技開箱與長片評測。',
                'industries' => ['科技'],
                'regions' => ['香港', '台灣'],
                'budget_range' => 'HKD 10k–40k / 專案',
            ],
            [
                'email' => 'brand.wander@kold.local',
                'name' => 'Wander Pack',
                'provider_id' => 'seed-brand-wander',
                'company_name' => 'Wander Pack',
                'bio' => '旅行配件品牌，適合旅遊／戶外創作者長期合作。',
                'industries' => ['旅遊', '戶外'],
                'regions' => ['香港', '台灣', '日本'],
                'budget_range' => 'HKD 6k–22k / 專案',
            ],
        ];

        foreach ($brands as $data) {
            $user = User::query()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'role' => 'brand',
                    'provider' => 'demo',
                    'provider_id' => $data['provider_id'],
                    'email_verified_at' => now(),
                    'avatar' => $this->avatarUrl($data['provider_id']),
                ]
            );

            BrandProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'company_name' => $data['company_name'],
                    'bio' => $data['bio'],
                    'industries' => $data['industries'],
                    'regions' => $data['regions'],
                    'budget_range' => $data['budget_range'],
                    'status' => 'published',
                ]
            );
        }
    }

    protected function avatarUrl(string $seed): string
    {
        return 'https://picsum.photos/seed/'.rawurlencode($seed).'/240/240';
    }

    protected function photoUrl(string $seed): string
    {
        return 'https://picsum.photos/seed/'.rawurlencode($seed).'/800/1000';
    }
}
