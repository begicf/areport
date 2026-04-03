@extends('layouts.app')

@section('shell_class', 'app-shell app-shell-auth')

@section('content')
    <div class="app-auth-shell">
        <div class="app-auth-card">
            <div class="app-auth-grid">
                <div class="app-auth-aside">
                    <div class="app-page-kicker">
                        <i class="fas fa-shield-halved"></i>
                        Secure access
                    </div>
                    <h1 class="app-page-title">Sign in to AReport</h1>
                    <p class="app-page-copy">
                        Access taxonomy management, reporting modules, and financial data workflows from one interface.
                    </p>
                </div>
                <div class="app-auth-body">
                    @include('flash.flash-message')

                    <form method="POST" action="{{ route('login') }}" class="app-form-stack">
                        @csrf

                        <div>
                            <label for="email" class="form-label">Email address</label>
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                                   name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                            @error('email')
                                <span class="invalid-feedback d-block" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="form-label">Password</label>
                            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror"
                                   name="password" required autocomplete="current-password">
                            @error('password')
                                <span class="invalid-feedback d-block" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                <label class="form-check-label" for="remember">
                                    Remember me
                                </label>
                            </div>

                            @if (Route::has('password.request'))
                                <a class="app-form-help" href="{{ route('password.request') }}">
                                    Forgot your password?
                                </a>
                            @endif
                        </div>

                        <div class="app-actions">
                            <button type="submit" class="btn btn-primary px-4">
                                Sign in
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
