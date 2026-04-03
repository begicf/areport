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
