@extends('layouts.app')

@section('content')
    <div class="app-auth-shell">
        <div class="app-auth-card">
            <div class="app-auth-grid">
                <div class="app-auth-aside">
                    <div class="app-page-kicker">
                        <i class="fas fa-user-plus"></i>
                        Account setup
                    </div>
                    <h1 class="app-page-title">Create a new workspace account</h1>
                    <p class="app-page-copy">
                        Register a user to manage taxonomies, open reporting modules, and maintain submission data.
                    </p>
                </div>
                <div class="app-auth-body">
                    @include('flash.flash-message')

                    <form method="POST" action="{{ route('register') }}" class="app-form-stack">
                        @csrf

                        <div>
                            <label for="name" class="form-label">Full name</label>
                            <input id="name" type="text" class="form-control @error('name') is-invalid @enderror"
                                   name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>
                            @error('name')
                                <span class="invalid-feedback d-block" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div>
                            <label for="email" class="form-label">Email address</label>
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                                   name="email" value="{{ old('email') }}" required autocomplete="email">
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
                                Register
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
