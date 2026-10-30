<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Areport') }}</title>

    <!-- Scripts -->
    <script src="{{ asset('js/jquerylib.js') }}" ></script>

    <script src="{{ asset('js/app.js') }}" defer></script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

    {{-- One desktop theme for the whole app: flat, professional, consistent on every page. --}}
    <style>
        body { background: #eef2f6 !important; color: #0f172a; }

        /* Top navigation: a single flat band (no web rounding / glass / shadow) */
        .app-navbar { padding: 0 !important; background: #fff !important; border-bottom: 1px solid #cbd5e1; }
        .app-navbar .container-fluid { padding: 0 !important; }
        .app-navbar-surface { border: 0 !important; border-radius: 0 !important; background: #fff !important;
            backdrop-filter: none !important; box-shadow: none !important; padding: .3rem 1rem !important; gap: .8rem; }
        .app-navbar-brand img { height: 34px !important; }
        .app-navbar-title { font-size: .92rem; }
        .app-navbar-subtitle { font-size: .62rem; letter-spacing: .12em; }
        .app-navbar .nav-link { border-radius: 2px !important; padding: .4rem .7rem !important; font-weight: 600; }
        .app-navbar .nav-link:hover, .app-navbar .nav-link:focus, .app-navbar .nav-link.active { background: #e6eef7; }
        .app-navbar-right { margin-left: auto; }
        .app-navbar-right .navbar-nav { flex-direction: row; align-items: center; }
        .app-navbar-right .dropdown-toggle { color: var(--ink); font-weight: 600; }

        /* ============================================================
           Elegant design system — blue (function) + amber (identity).
           Soft rounding, light hairlines, restrained emphasis.
           Shared tokens + components, reused across every page.
           ============================================================ */
        :root {
            --blue: #2f557d; --blue-d: #243f5c; --blue-soft: #eef3f9; --blue-bd: #dbe4ef;
            --amber: #c2731f; --amber-soft: #e4902f;
            --ink: #1f2933; --muted: #7b8794; --faint: #9aa5b1; --line: #e6e9ee; --band: #fafbfc;
        }
        body { background: #f4f6f9 !important; color: var(--ink); }

        /* Soft, modern rounding (supersedes the old flat 2px) */
        .btn, .form-control, .form-select, .input-group-text, .dropdown-menu,
        .page-link, .list-group-item { border-radius: 7px !important; }
        .card, .modal-content { border-radius: 10px !important; }
        .badge, .badge.rounded-pill { border-radius: 999px !important; }
        .btn { font-weight: 500; }
        .card { border: 1px solid var(--line); box-shadow: 0 1px 2px rgba(16,24,40,.04); }
        .card-header { background: linear-gradient(#fbfcfe, #fff); border-bottom: 1px solid #eef1f4; }

        /* Links, accents, controls */
        a { color: var(--blue); } a:hover { color: var(--blue-d); }
        .text-primary { color: var(--blue) !important; }
        .text-bg-primary { background-color: var(--blue) !important; }
        .btn-primary { background-color: var(--blue); border-color: var(--blue); }
        .btn-primary:hover, .btn-primary:focus, .btn-primary:active, .btn-primary:first-child:active {
            background-color: var(--blue-d) !important; border-color: var(--blue-d) !important; }
        .btn-outline-primary { color: var(--blue); border-color: var(--blue-bd); }
        .btn-outline-primary:hover { background: var(--blue); border-color: var(--blue); color: #fff; }
        .btn:disabled, .btn.disabled { opacity: .5; cursor: not-allowed; }
        .form-control:focus, .form-select:focus { border-color: var(--blue); box-shadow: 0 0 0 .18rem rgba(47,85,125,.12); }
        code { background: var(--band); border: 1px solid var(--line); padding: .05rem .3rem; border-radius: 4px;
            font-family: "SFMono-Regular","Consolas","Liberation Mono",monospace; font-size: .86em; color: var(--blue); }
        .alert { border-radius: 8px !important; box-shadow: none !important; }
        .dpm-loading { font-size: .82rem; color: var(--faint); padding: 1rem; text-align: center; }

        /* Navbar — elegant, amber underline marks the active item */
        .app-navbar { border-bottom: 1px solid var(--line); }
        .app-navbar .nav-link { color: #52606d; font-weight: 500; border-bottom: 2px solid transparent; border-radius: 6px 6px 0 0; }
        .app-navbar .nav-link:hover, .app-navbar .nav-link:focus { color: var(--ink); background: #f0f2f5; }
        .app-navbar .nav-link.active { color: var(--ink); font-weight: 600; background: transparent; border-bottom-color: var(--amber-soft); }
        .app-navbar-subtitle { color: var(--faint); }

        /* Shared page scaffold (DPM Architect / Settings / taxonomy…) */
        .app-page { padding: 1.3rem 1.5rem 2rem; }
        .app-page-kicker { font-size: .64rem; font-weight: 700; text-transform: uppercase; letter-spacing: .11em; color: var(--amber); }
        .app-page-title { font-size: 1.32rem; font-weight: 600; color: var(--ink); margin: 0; letter-spacing: -.01em; }
        .app-page-header, .app-panel {
            border-radius: 10px !important; box-shadow: 0 1px 2px rgba(16,24,40,.04) !important; border: 1px solid var(--line) !important; }
        .app-page-header { border-top: 1px solid var(--line) !important; padding: 1rem 1.1rem !important; margin-bottom: 1rem !important; }
        .app-panel { padding: 1rem 1.1rem; }
        .app-tree-shell, .app-tree-toolbar, .app-report-controls, .app-table-shell,
        .app-report-panel, .app-report-header, .app-report-inspector, .app-report-table-shell,
        .app-auth-card, .app-auth-aside, .app-report-controls-bar,
        .app-module-instance-empty, .app-module-instance-card, .app-module-instance-icon,
        .app-module-instance-badge { border-radius: 8px !important; }
        .app-module-instance-icon { background: var(--blue-soft) !important; color: var(--blue) !important; border: 1px solid var(--blue-bd) !important; }

        /* Modals & plugin widgets */
        .modal-content { border: 1px solid var(--line); box-shadow: 0 10px 30px rgba(16,24,40,.12) !important; }
        .modal-content .modal-header { background: linear-gradient(#fbfcfe, #fff); border-bottom: 1px solid #eef1f4; }
        .bootstrap-select > .dropdown-toggle, .bootstrap-select .dropdown-menu,
        .ui-datepicker, .ui-widget-content, .jstree-default .jstree-anchor { border-radius: 7px !important; }

        /* ===== Reusable elegant components (.ax-*) ===== */
        .ax-stack > * + * { margin-top: 1rem; }
        .ax-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; }
        .ax-eyebrow { font-size: .64rem; font-weight: 700; letter-spacing: .11em; text-transform: uppercase; color: var(--amber); display: inline-flex; gap: .4rem; align-items: center; }
        .ax-title { font-size: 1.32rem; font-weight: 600; color: var(--ink); margin: .2rem 0 0; letter-spacing: -.01em; }
        .ax-sub { font-size: .82rem; color: var(--muted); margin: .25rem 0 0; }
        .ax-card { background: #fff; border: 1px solid var(--line); border-radius: 10px; box-shadow: 0 1px 2px rgba(16,24,40,.04); overflow: hidden; }
        .ax-panel-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; padding: 1rem 1.1rem; border-bottom: 1px solid #eef1f4; background: linear-gradient(#fbfcfe, #fff); }
        .ax-panel-title { font-size: 1.02rem; font-weight: 600; color: var(--ink); margin: .18rem 0 0; }
        .ax-summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; }
        .ax-stat { position: relative; overflow: hidden; background: #fff; border: 1px solid var(--line); border-radius: 10px; box-shadow: 0 1px 2px rgba(16,24,40,.04); padding: .85rem 1rem; }
        .ax-stat::before { content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: var(--blue); }
        .ax-stat-k { font-size: .66rem; font-weight: 600; letter-spacing: .07em; text-transform: uppercase; color: var(--faint); }
        .ax-stat-v { font-size: 1.6rem; font-weight: 600; color: var(--blue); line-height: 1.1; margin-top: .25rem; font-variant-numeric: tabular-nums; }
        .ax-stat-v.sm { font-size: .95rem; font-weight: 500; color: var(--muted); margin-top: .5rem; }
        .ax-table { width: 100%; margin: 0; font-size: .84rem; }
        .ax-table thead th { text-align: left; font-size: .66rem; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; color: var(--faint); padding: .6rem 1.1rem; border-bottom: 1px solid #eef1f4; background: #fff; }
        .ax-table tbody td { padding: .7rem 1.1rem; border-bottom: 1px solid #f2f4f7; vertical-align: middle; }
        .ax-table tbody tr:hover { background: var(--band); }
        .ax-badge { display: inline-flex; padding: .12rem .5rem; background: var(--blue-soft); color: var(--blue); border: 1px solid var(--blue-bd); border-radius: 6px; font-size: .7rem; font-weight: 600; }

        /* Active-taxonomy chip — explicit & readable (solid blue, white text, amber dot) */
        .ax-tax-label { font-size: .6rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: var(--faint); }
        .ax-tax-badge { display: inline-flex; align-items: center; gap: .45rem; background: var(--blue); color: #fff !important;
            padding: .26rem .65rem; border-radius: 999px; font-size: .74rem; font-weight: 700; letter-spacing: .01em; text-decoration: none; line-height: 1; }
        .ax-tax-badge::before { content: ""; width: 7px; height: 7px; border-radius: 50%; background: var(--amber-soft); flex: 0 0 auto; }
        .ax-tax-badge:hover, .ax-tax-badge:focus { background: var(--blue-d); color: #fff !important; }
        .ax-tax-badge.is-unset { background: #eef1f4; color: #52606d !important; }
        .ax-tax-badge.is-unset::before { background: #b9c2cd; }
    </style>
    @stack('styles')
</head>
<body>
<div id="app">
    <nav class="navbar navbar-expand-md navbar-light app-navbar">
        <div class="container-fluid">
            <div class="app-navbar-surface">
                <a class="navbar-brand app-navbar-brand" href="{{ url('/home') }}">
                    <img height="45" src="{{ asset('images/logo.svg') }}" alt="{{ config('app.name', 'AReport') }}">
                    <span class="app-navbar-copy">
                        <span class="app-navbar-title">{{ config('app.name', 'AReport') }}</span>
                        <span class="app-navbar-subtitle">Financial reporting workspace</span>
                    </span>
                </a>
                <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
                        aria-controls="navbarSupportedContent" aria-expanded="false"
                        aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    @auth
                        <ul class="navbar-nav me-auto">
                            <x-menu-menu-component type="v"/>
                            @foreach(config('areport.views.authenticated_navigation', []) as $navigationView)
                                @includeIf($navigationView)
                            @endforeach
                        </ul>
                    @endauth

                    {{-- Right cluster: active taxonomy sits beside the account menu --}}
                    <div class="app-navbar-right d-flex align-items-center gap-3">
                        @auth
                            <div class="d-none d-md-flex align-items-center gap-2">
                                <span class="ax-tax-label">Active taxonomy</span>
                                @if(!empty($activeTaxonomy))
                                    <a href="{{ url('/taxonomy/managing') }}" class="ax-tax-badge" title="Active taxonomy — manage">
                                        {{ $activeTaxonomy->name }}
                                    </a>
                                @else
                                    <a href="{{ url('/taxonomy/managing') }}" class="ax-tax-badge is-unset" title="No taxonomy active — set one">
                                        Not set
                                    </a>
                                @endif
                            </div>
                        @endauth

                        <ul class="navbar-nav">
                            @guest
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">{{ __('Sign in') }}</a>
                                </li>
                                @if (Route::has('register'))
                                    <li class="nav-item">
                                        <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                                    </li>
                                @endif
                            @else
                                <li class="nav-item dropdown">
                                    <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button"
                                       data-bs-toggle="dropdown" aria-expanded="false" v-pre>
                                        <i class="fas fa-circle-user me-1" style="color:var(--muted)"></i>{{ Auth::user()->name }}
                                    </a>

                                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                        <a class="dropdown-item" href="{{ route('logout') }}"
                                           onclick="event.preventDefault();
                                                             document.getElementById('logout-form').submit();">
                                            {{ __('Sign out') }}
                                        </a>

                                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                            @csrf
                                        </form>
                                    </div>
                                </li>
                            @endguest
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</div>
<main class="app-main">
    <div class="@yield('shell_class', 'app-shell')">
        @yield('content')
    </div>
</main>

    @stack('scripts')
</body>
</html>
