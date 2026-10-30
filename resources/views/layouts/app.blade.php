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
        .app-navbar .d-md-flex.align-items-center, .app-navbar .d-none.d-md-flex { margin-left: auto !important; }

        /* Flat desktop controls everywhere */
        .btn, .form-control, .form-select, .input-group-text, .dropdown-menu,
        .card, .modal-content, .alert, .list-group-item, .page-link,
        .badge, .badge.rounded-pill { border-radius: 2px !important; }
        .btn { font-weight: 600; }
        .card { border: 1px solid #cbd5e1; box-shadow: none; }
        .card-header { background: #f1f5f9; border-bottom: 1px solid #cbd5e1; }

        /* Shared page scaffold used by DPM Architect / Settings and reusable elsewhere */
        .app-page { padding: .9rem 1rem 2rem; }
        .app-page-kicker { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: #0f766e; }
        .app-page-title { font-size: 1.3rem; font-weight: 800; color: #0f172a; margin: 0; }

        /* Flatten the shared design-system panels (module explorer, taxonomy, reports…) */
        .app-page-header, .app-panel {
            border-radius: 0 !important; box-shadow: none !important; border: 1px solid #cbd5e1 !important; }
        .app-page-header { border-top: 2px solid #0f766e !important; padding: .7rem .9rem !important; margin-bottom: .6rem !important; }
        .app-panel { padding: .8rem .9rem; }
        .app-tree-shell, .app-tree-toolbar, .app-report-controls, .app-table-shell,
        .app-report-panel, .app-report-header, .app-report-inspector, .app-report-table-shell,
        .app-auth-card, .app-auth-aside, .app-report-controls-bar,
        .app-module-instance-empty, .app-module-instance-card, .app-module-instance-icon,
        .app-module-instance-badge { border-radius: 2px !important; box-shadow: none !important; }
        .app-module-instance-icon { background: #f0fdfa !important; color: #0f766e !important; border: 1px solid #99f6e4 !important; }

        /* Flatten plugin widgets & modals so every page matches the desktop theme */
        .modal-content { border-radius: 2px !important; box-shadow: none !important; border: 1px solid #94a3b8; }
        .modal-content .modal-header { background: #f1f5f9; border-bottom: 1px solid #cbd5e1; }
        .bootstrap-select > .dropdown-toggle, .bootstrap-select .dropdown-menu,
        .ui-datepicker, .ui-widget-content, .jstree-default .jstree-anchor { border-radius: 2px !important; }
        .btn:disabled, .btn.disabled { opacity: .5; cursor: not-allowed; }
        /* Primary = teal accent (match the desktop theme) */
        .btn-primary { background-color: #0f766e; border-color: #0f766e; }
        .btn-primary:hover, .btn-primary:focus, .btn-primary:active, .btn-primary:first-child:active {
            background-color: #0b5c55 !important; border-color: #0b5c55 !important; }
        .btn-outline-primary { color: #0f766e; border-color: #0f766e; }
        .btn-outline-primary:hover { background-color: #0f766e; border-color: #0f766e; color: #fff; }
        .text-bg-primary { background-color: #0f766e !important; }
        .text-primary { color: #0f766e !important; }
        a { color: #0f766e; }
        code { background: #f8fafc; border: 1px solid #e2e8f0; padding: .05rem .3rem; border-radius: 2px;
            font-family: "SFMono-Regular","Consolas","Liberation Mono",monospace; font-size: .86em; color: #0f766e; }
        .alert { border-radius: 2px !important; box-shadow: none !important; }
        .dpm-loading { font-size: .82rem; color: #94a3b8; padding: 1rem; text-align: center; }
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

                        <div class="d-none d-md-flex align-items-center gap-2 ms-md-3">
                            <span class="text-uppercase small text-muted fw-semibold">Active taxonomy</span>
                            @if(!empty($activeTaxonomy))
                                <a href="{{ url('/taxonomy/managing') }}" class="badge rounded-pill text-bg-primary text-decoration-none">
                                    {{ $activeTaxonomy->name }}
                                </a>
                            @else
                                <a href="{{ url('/taxonomy/managing') }}" class="badge rounded-pill text-bg-secondary text-decoration-none">
                                    Not set
                                </a>
                            @endif
                        </div>
                    @endauth

                    <ul class="navbar-nav ms-auto">
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
                                    {{ Auth::user()->name }}
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
