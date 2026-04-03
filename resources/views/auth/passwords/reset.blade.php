@extends('layouts.app')

@section('content')
    <div class="app-auth-shell">
        <div class="app-auth-card">
            <div class="app-auth-grid">
                <div class="app-auth-aside">
                    <div class="app-page-kicker">
                        <i class="fas fa-rotate"></i>
                        Reset access
                    </div>
                    <h1 class="app-page-title">Set a new password</h1>
                    <p class="app-page-copy">
                        Choose a new password for your AReport account and confirm it before continuing.
                    </p>
                </div>
                <div class="app-auth-body">
                    <form method="POST" action="{{ route('password.update') }}" class="app-form-stack">
                        @csrf

                        <input type="hidden" name="token" value="{{ $token }}">

                        <div>
                            <label for="email" class="form-label">Email address</label>
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                                   name="email" value="{{ $email ?? old('email') }}" required autocomplete="email" autofocus>
                            @error('email')
                                <span class="invalid-feedback d-block" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="form-label">Password</label>
                            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror"
                                   name="password" required autocomplete="new-password">
                            @error('password')
                                <span class="invalid-feedback d-block" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div>
                            <label for="password-confirm" class="form-label">Confirm password</label>
                            <input id="password-confirm" type="password" class="form-control"
                                   name="password_confirmation" required autocomplete="new-password">
                        </div>

                        <div class="app-actions">
                            <button type="submit" class="btn btn-primary px-4">
                                Reset password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
