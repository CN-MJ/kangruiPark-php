<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', '管理端') · 赣南健康城</title>
    <style>
        :root {
            --bg: #f3f5f2;
            --panel: #ffffff;
            --ink: #1a2e1f;
            --muted: #5c6b60;
            --line: #d8e0d6;
            --accent: #1f6b45;
            --accent-soft: #e8f3ec;
            --danger: #b42318;
            --warn: #9a6700;
            --radius: 10px;
            --font: "Segoe UI", "PingFang SC", "Microsoft YaHei", sans-serif;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: var(--font);
            color: var(--ink);
            background:
                radial-gradient(900px 420px at 0% -10%, #dceee3 0%, transparent 55%),
                radial-gradient(700px 380px at 100% 0%, #e7efe8 0%, transparent 50%),
                var(--bg);
            min-height: 100vh;
        }
        a { color: var(--accent); text-decoration: none; }
        a:hover { text-decoration: underline; }
        .shell { display: flex; min-height: 100vh; }
        .side {
            width: 220px;
            background: #12261a;
            color: #e8f0ea;
            padding: 1.25rem 1rem;
            flex-shrink: 0;
        }
        .brand {
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: .02em;
            margin: 0 0 .25rem;
        }
        .brand-sub { color: #9bb5a5; font-size: .78rem; margin-bottom: 1.5rem; }
        .nav a {
            display: block;
            color: #d4e4d9;
            padding: .55rem .7rem;
            border-radius: 8px;
            margin-bottom: .25rem;
            text-decoration: none;
        }
        .nav a:hover, .nav a.active {
            background: rgba(255,255,255,.08);
            color: #fff;
            text-decoration: none;
        }
        .side-foot {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255,255,255,.1);
            font-size: .8rem;
            color: #9bb5a5;
        }
        .side-foot form { margin-top: .75rem; }
        .main { flex: 1; padding: 1.5rem 1.75rem 2.5rem; max-width: 1100px; }
        h1 { font-size: 1.35rem; margin: 0 0 .35rem; }
        .lead { color: var(--muted); margin: 0 0 1.25rem; font-size: .92rem; }
        .panel {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            padding: 1rem 1.1rem;
            margin-bottom: 1rem;
        }
        .flash {
            padding: .7rem .9rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: .9rem;
        }
        .flash.ok { background: var(--accent-soft); color: #14532d; }
        .flash.err { background: #fef3f2; color: var(--danger); }
        table { width: 100%; border-collapse: collapse; font-size: .88rem; }
        th, td { text-align: left; padding: .55rem .4rem; border-bottom: 1px solid var(--line); vertical-align: top; }
        th { color: var(--muted); font-weight: 600; font-size: .8rem; }
        input[type=text], input[type=password], select {
            width: 100%;
            max-width: 220px;
            padding: .45rem .55rem;
            border: 1px solid var(--line);
            border-radius: 7px;
            font: inherit;
            background: #fff;
        }
        .btn {
            display: inline-block;
            border: 0;
            border-radius: 7px;
            padding: .45rem .85rem;
            font: inherit;
            cursor: pointer;
            background: var(--accent);
            color: #fff;
        }
        .btn:hover { filter: brightness(1.05); }
        .btn-ghost {
            background: transparent;
            color: #d4e4d9;
            border: 1px solid rgba(255,255,255,.2);
        }
        .btn-sm { padding: .3rem .55rem; font-size: .8rem; }
        .filters { display: flex; flex-wrap: wrap; gap: .6rem; align-items: end; margin-bottom: .8rem; }
        .filters label { display: flex; flex-direction: column; gap: .25rem; font-size: .78rem; color: var(--muted); }
        .muted { color: var(--muted); }
        .pager { margin-top: .9rem; font-size: .85rem; }
        .pager a { margin-right: .5rem; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: .75rem; }
        .stat {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            padding: .9rem 1rem;
        }
        .stat b { display: block; font-size: 1.4rem; }
        .stat span { color: var(--muted); font-size: .8rem; }
        .row-form { display: flex; flex-wrap: wrap; gap: .4rem; align-items: center; }
        .row-form input, .row-form select { max-width: 140px; }
        .tag {
            display: inline-block;
            padding: .1rem .4rem;
            border-radius: 4px;
            background: var(--accent-soft);
            color: #14532d;
            font-size: .75rem;
        }
        .login-wrap {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 1.5rem;
        }
        .login-card {
            width: 100%;
            max-width: 380px;
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 1.6rem 1.5rem 1.4rem;
            box-shadow: 0 12px 40px rgba(18, 38, 26, .06);
        }
        .login-card h1 { margin-bottom: .2rem; }
        .field { margin-bottom: .9rem; }
        .field label { display: block; font-size: .82rem; color: var(--muted); margin-bottom: .3rem; }
        .field input { max-width: none; width: 100%; }
        .login-card .btn { width: 100%; margin-top: .3rem; }
        code { font-size: .82em; background: #eef2ef; padding: .05rem .3rem; border-radius: 4px; }
        @media (max-width: 800px) {
            .shell { flex-direction: column; }
            .side { width: 100%; }
            .main { padding: 1rem; }
        }
    </style>
</head>
<body>
@if (request()->routeIs('admin.login'))
    @yield('content')
@else
<div class="shell">
    <aside class="side">
        <p class="brand">赣南健康城</p>
        <p class="brand-sub">园区 Web 管理端</p>
        <nav class="nav">
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">概览</a>
            <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">用户与岗位</a>
            <a href="{{ route('admin.audit-logs.index') }}" class="{{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}">审计日志</a>
        </nav>
        <div class="side-foot">
            <div>{{ auth()->user()?->name ?: auth()->user()?->phone }}</div>
            <div class="muted" style="color:#9bb5a5">园区管理员</div>
            <form method="post" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="btn btn-ghost btn-sm">退出</button>
            </form>
        </div>
    </aside>
    <main class="main">
        @if (session('status'))
            <div class="flash ok">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="flash err">{{ $errors->first() }}</div>
        @endif
        @yield('content')
    </main>
</div>
@endif
</body>
</html>
