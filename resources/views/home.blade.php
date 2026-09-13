@extends('layouts.app')

@section('title', 'KOLD — KOL 媒合平台')

@section('content')
<section class="hero">
    <aside class="kol-wall" aria-hidden="true" id="kolWall"></aside>
    <div class="hero-copy">
        <h1 class="hero-brand">KOL<em>D</em></h1>
        <p>找到對的創作者，用社群貼文幫品牌曝光。註冊、建檔、搜尋、送出合作邀請——一體兩面的媒合平台。</p>
        <div class="hero-actions" id="start">
            <a class="btn btn-primary" href="{{ route('auth.redirect', 'google') }}">用 Google 開始</a>
            <a class="btn btn-ghost" href="{{ route('auth.redirect', 'facebook') }}">用 Meta 開始</a>
        </div>
    </div>
</section>

@if (config('kold.demo_login_enabled'))
<section class="section">
    <h2>先體驗完整流程</h2>
    <p class="lead">SSO 金鑰尚未設定時，可用示範帳號走完建檔、探索與聯絡請求。</p>
    <div class="grid grid-2">
        <form class="panel" method="POST" action="{{ route('auth.demo') }}">
            @csrf
            <input type="hidden" name="role" value="kol">
            <h3 style="margin-top:0;font-family:var(--font-display)">以 KOL 身分進入</h3>
            <p style="opacity:.75">查看品牌、完善檔案、接受合作邀請。</p>
            <button class="btn btn-accent" type="submit">示範登入 · KOL</button>
        </form>
        <form class="panel" method="POST" action="{{ route('auth.demo') }}">
            @csrf
            <input type="hidden" name="role" value="brand">
            <h3 style="margin-top:0;font-family:var(--font-display)">以品牌身分進入</h3>
            <p style="opacity:.75">搜尋創作者、送出聯絡請求、開啟對話。</p>
            <button class="btn btn-primary" type="submit">示範登入 · 品牌</button>
        </form>
    </div>
</section>
@endif
@endsection

@section('scripts')
<script>
(() => {
  const POSTS = [
    { handle: "mika.eats", niche: "美食 · 香港", plat: "IG", likes: "12.4k", shares: "386", caption: "週末探店", img: "https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=480&h=480&fit=crop&q=80", avatar: "https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=80&h=80&fit=crop&q=80" },
    { handle: "kai.moves", niche: "健身 · 訓練日記", plat: "IG", likes: "8.1k", shares: "214", caption: "合作訓練", img: "https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?w=480&h=480&fit=crop&q=80", avatar: "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=80&h=80&fit=crop&q=80" },
    { handle: "yuki.skincare", niche: "美妝實測", plat: "IG", likes: "21.6k", shares: "902", caption: "品牌試用", img: "https://images.unsplash.com/photo-1596462502278-27bfdc403348?w=480&h=480&fit=crop&q=80", avatar: "https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=80&h=80&fit=crop&q=80" },
    { handle: "travel.leo", niche: "旅拍 · Vlog", plat: "IG", likes: "15.2k", shares: "541", caption: "景點開箱", img: "https://images.unsplash.com/photo-1488646953014-85cb44e25828?w=480&h=480&fit=crop&q=80", avatar: "https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=80&h=80&fit=crop&q=80" },
    { handle: "home.by.ann", niche: "生活風格", plat: "IG", likes: "6.8k", shares: "173", caption: "居家佈置", img: "https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?w=480&h=480&fit=crop&q=80", avatar: "https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=80&h=80&fit=crop&q=80" },
    { handle: "tech.noah", niche: "3C 開箱", plat: "IG", likes: "9.4k", shares: "328", caption: "新品實測", img: "https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=480&h=480&fit=crop&q=80", avatar: "https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=80&h=80&fit=crop&q=80" },
    { handle: "street.mina", niche: "穿搭日常", plat: "IG", likes: "18.9k", shares: "764", caption: "OOTD", img: "https://images.unsplash.com/photo-1483985988355-763728e1935b?w=480&h=480&fit=crop&q=80", avatar: "https://images.unsplash.com/photo-1529626455594-4ff0802cfb7e?w=80&h=80&fit=crop&q=80" },
    { handle: "brew.jay", niche: "咖啡 · 品牌", plat: "IG", likes: "4.2k", shares: "119", caption: "聯名杯測", img: "https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=480&h=480&fit=crop&q=80", avatar: "https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=80&h=80&fit=crop&q=80" },
    { handle: "fit.cara", niche: "瑜伽 · Wellness", plat: "IG", likes: "11.0k", shares: "405", caption: "早晨流程", img: "https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=480&h=480&fit=crop&q=80", avatar: "https://images.unsplash.com/photo-1517841905240-472988babdf9?w=80&h=80&fit=crop&q=80" },
    { handle: "cook.tom", niche: "料理短影音", plat: "IG", likes: "27.3k", shares: "1.2k", caption: "品牌食譜", img: "https://images.unsplash.com/photo-1556910103-1c02745aae4d?w=480&h=480&fit=crop&q=80", avatar: "https://images.unsplash.com/photo-1463453091185-61582044d556?w=80&h=80&fit=crop&q=80" },
    { handle: "pet.luna", niche: "寵物日常", plat: "IG", likes: "33.1k", shares: "1.5k", caption: "好物分享", img: "https://images.unsplash.com/photo-1587300003388-59208cc962cb?w=480&h=480&fit=crop&q=80", avatar: "https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=80&h=80&fit=crop&q=80" },
    { handle: "film.rex", niche: "短片 · 廣告感", plat: "IG", likes: "7.6k", shares: "291", caption: "拍攝花絮", img: "https://images.unsplash.com/photo-1492691527719-9d1e07e534b4?w=480&h=480&fit=crop&q=80", avatar: "https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=80&h=80&fit=crop&q=80" },
    { handle: "glow.sue", niche: "彩妝教學", plat: "IG", likes: "14.8k", shares: "612", caption: "新品上手", img: "https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?w=480&h=480&fit=crop&q=80", avatar: "https://images.unsplash.com/photo-1487412720507-e7ab37603c6f?w=80&h=80&fit=crop&q=80" },
    { handle: "run.alex", niche: "跑步裝備", plat: "IG", likes: "5.5k", shares: "148", caption: "路跑實測", img: "https://images.unsplash.com/photo-1476480862126-209bfaa8edc8?w=480&h=480&fit=crop&q=80", avatar: "https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?w=80&h=80&fit=crop&q=80" },
    { handle: "desk.mei", niche: "工作空間", plat: "IG", likes: "3.9k", shares: "97", caption: "品牌文具", img: "https://images.unsplash.com/photo-1497366216548-37526070297c?w=480&h=480&fit=crop&q=80", avatar: "https://images.unsplash.com/photo-1531746020798-e6953c6e8e04?w=80&h=80&fit=crop&q=80" },
    { handle: "plant.owen", niche: "植栽生活", plat: "IG", likes: "6.1k", shares: "166", caption: "開箱盆栽", img: "https://images.unsplash.com/photo-1463936577641-acd31051ba7e?w=480&h=480&fit=crop&q=80", avatar: "https://images.unsplash.com/photo-1521119989659-a83eee488004?w=80&h=80&fit=crop&q=80" },
    { handle: "bake.iris", niche: "甜點工作室", plat: "IG", likes: "10.7k", shares: "433", caption: "聯名甜點", img: "https://images.unsplash.com/photo-1488477181946-6428a0291777?w=480&h=480&fit=crop&q=80", avatar: "https://images.unsplash.com/photo-1488426862026-3ee34a7d66df?w=80&h=80&fit=crop&q=80" },
    { handle: "city.ken", niche: "街頭攝影", plat: "IG", likes: "8.8k", shares: "257", caption: "品牌街拍", img: "https://images.unsplash.com/photo-1449824913935-59a10b8d2000?w=480&h=480&fit=crop&q=80", avatar: "https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=80&h=80&fit=crop&q=80" },
  ];

  const COLUMNS = [
    { dir: "up", dur: "48s", count: 6, offset: 0 },
    { dir: "down", dur: "56s", count: 6, offset: 6 },
    { dir: "up", dur: "42s", count: 6, offset: 12 },
  ];

  const ICON_HEART = `<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>`;
  const ICON_SHARE = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M4 12v7a1 1 0 001 1h14a1 1 0 001-1v-7M16 6l-4-4-4 4M12 2v14"/></svg>`;

  function postCard(p) {
    const el = document.createElement("article");
    el.className = "kol-post";
    el.innerHTML = `
      <div class="kol-post-head">
        <img class="kol-avatar" src="${p.avatar}" alt="" loading="lazy" decoding="async" />
        <div class="kol-post-meta">
          <span class="kol-handle">@${p.handle}</span>
          <span class="kol-niche">${p.niche}</span>
        </div>
        <span class="kol-plat">${p.plat}</span>
      </div>
      <div class="kol-media">
        <img src="${p.img}" alt="" loading="lazy" decoding="async" />
      </div>
      <div class="kol-post-foot">
        <div class="kol-stats">
          <span class="kol-stat likes">${ICON_HEART}${p.likes}</span>
          <span class="kol-stat shares">${ICON_SHARE}${p.shares}</span>
        </div>
        <span class="kol-caption">${p.caption}</span>
      </div>
    `;
    return el;
  }

  const wall = document.getElementById("kolWall");
  if (!wall) return;

  COLUMNS.forEach((col) => {
    const column = document.createElement("div");
    column.className = "kol-col";
    column.dataset.dir = col.dir;
    column.style.setProperty("--dur", col.dur);

    const track = document.createElement("div");
    track.className = "kol-track";

    const slice = POSTS.slice(col.offset, col.offset + col.count);
    [...slice, ...slice].forEach((p) => track.appendChild(postCard(p)));

    column.appendChild(track);
    wall.appendChild(column);
  });
})();
</script>
@endsection
