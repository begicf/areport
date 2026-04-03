@extends('layouts.app')

@section('content')
    <div class="app-auth-shell">
        <div class="app-auth-card">
            <div class="app-auth-grid">
                <div class="app-auth-aside">
                    <div class="app-page-kicker">
                        <i class="fas fa-key"></i>
                        Password recovery
                    </div>
                    <h1 class="app-page-title">Request a password reset link</h1>
                    <p class="app-page-copy">
                        Enter your email address and we will send you a secure link for resetting your password.
                    </p>
                </div>
                <div class="app-auth-body">
                    @if (session('status'))
                        <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
                            {{ session('status') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}" class="app-form-stack">
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

                        <div class="app-actions">
                            <button type="submit" class="btn btn-primary px-4">
                                Send reset link
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
