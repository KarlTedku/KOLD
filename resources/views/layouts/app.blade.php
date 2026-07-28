<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'KOLD')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,560;9..144,700&family=Manrope:wght@400;500;650;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ url('/css/app.css') }}">
</head>
<body>
<header class="site-header" id="siteHeader">
    <a class="brand" href="{{ route('home') }}">KOL<span>D</span></a>
    <nav class="nav">
        @auth
            <a href="{{ route('dashboard') }}">總覽</a>
            <a href="{{ route('discover.index') }}">探索</a>
            <a href="{{ route('contact.inbox') }}">收件匣</a>
            <a href="{{ route('conversations.index') }}">對話</a>
            <a href="{{ route('profile.edit') }}">我的檔案</a>
            <form action="{{ route('logout') }}" method="POST" style="display:inline">
                @csrf
                <button class="btn btn-ghost" type="submit">登出</button>
            </form>
        @else
            <a href="#start">開始媒合</a>
        @endauth
    </nav>
</header>

@if (session('status'))
    <div class="flash">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="flash error">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="flash error">
        {{ $errors->first() }}
    </div>
@endif

<main>
    @yield('content')
</main>

<footer class="site-footer">
    KOLD — KOL 與品牌雙向媒合。不含合約與金流。
</footer>

<script>
  const header = document.getElementById('siteHeader');
  const onScroll = () => header.classList.toggle('is-scrolled', window.scrollY > 8);
  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });
</script>
</body>
</html>
