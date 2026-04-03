@extends('layouts.app')

@section('content')
    <div class="app-auth-shell">
        <div class="app-auth-card">
            <div class="app-auth-grid">
                <div class="app-auth-aside">
                    <div class="app-page-kicker">
                        <i class="fas fa-lock"></i>
                        Confirmation required
                    </div>
                    <h1 class="app-page-title">Confirm your password</h1>
                    <p class="app-page-copy">
                        For security reasons, please re-enter your password before continuing to the requested action.
                    </p>
                </div>
                <div class="app-auth-body">
                    <p class="app-form-help mb-4">Confirm your current password to continue.</p>

                    <form method="POST" action="{{ route('password.confirm') }}" class="app-form-stack">
                        @csrf

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

                        <div class="d-flex flex-wrap align-items-center gap-3">
                            <button type="submit" class="btn btn-primary px-4">
                                Confirm password
                            </button>

                            @if (Route::has('password.request'))
                                <a class="app-form-help" href="{{ route('password.request') }}">
                                    Forgot your password?
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
