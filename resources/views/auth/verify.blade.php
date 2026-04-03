@extends('layouts.app')

@section('content')
    <div class="app-auth-shell">
        <div class="app-auth-card">
            <div class="app-auth-grid">
                <div class="app-auth-aside">
                    <div class="app-page-kicker">
                        <i class="fas fa-envelope-open-text"></i>
                        Email verification
                    </div>
                    <h1 class="app-page-title">Verify your email address</h1>
                    <p class="app-page-copy">
                        Check your inbox for the verification link before using the rest of the workspace.
                    </p>
                </div>
                <div class="app-auth-body">
                    @if (session('resent'))
                        <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
                            A fresh verification link has been sent to your email address.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <p class="app-form-help mb-3">Before proceeding, please check your email for a verification link.</p>
                    <p class="app-form-help mb-4">
                        If you did not receive the email, you can request another verification message below.
                    </p>

                    <form method="POST" action="{{ route('verification.resend') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary px-4">Send another verification email</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
