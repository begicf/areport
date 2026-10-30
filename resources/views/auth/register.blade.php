@extends('layouts.app')

@push('styles')
    <style>
        .auth-shell { min-height: calc(100vh - 64px); display: flex; align-items: center; justify-content: center; padding: 2rem 1rem; }
        .auth-card { width: min(960px, 100%); display: grid; grid-template-columns: minmax(0, .95fr) minmax(0, 1.05fr);
            border: 1px solid var(--line); border-radius: 14px; overflow: hidden; box-shadow: 0 18px 50px rgba(16,24,40,.12); background: #fff; }
        .auth-aside { padding: 2rem 1.8rem; color: #fff;
            background: radial-gradient(700px 300px at 0% 0%, rgba(228,144,47,.25), transparent 55%), linear-gradient(150deg, #16304f 0%, #2f557d 80%); }
        .auth-aside .eyebrow { font-size: .64rem; font-weight: 700; letter-spacing: .13em; text-transform: uppercase; color: rgba(255,255,255,.75); }
        .auth-aside h1 { font-size: 1.5rem; font-weight: 700; letter-spacing: -.01em; margin: .5rem 0 0; }
        .auth-aside p { color: rgba(255,255,255,.82); font-size: .9rem; margin: .7rem 0 0; }
        .auth-aside ul { list-style: none; padding: 0; margin: 1.4rem 0 0; display: grid; gap: .6rem; }
        .auth-aside li { display: flex; gap: .6rem; align-items: flex-start; font-size: .88rem; color: rgba(255,255,255,.92); }
        .auth-aside li i { color: var(--amber-soft); margin-top: .2rem; }
        .auth-logo { height: 30px; margin-bottom: 1.4rem; filter: brightness(0) invert(1); opacity: .95; }
        .auth-body { padding: 2rem 1.8rem; }
        .auth-body h2 { font-size: 1.2rem; font-weight: 600; margin: 0 0 .2rem; }
        .auth-body .sub { color: var(--muted); font-size: .85rem; margin: 0 0 1.3rem; }
        .auth-form { display: grid; gap: .9rem; }
        .auth-form .form-label { font-size: .78rem; font-weight: 600; color: #52606d; margin-bottom: .25rem; }
        .auth-foot { margin-top: 1.2rem; font-size: .85rem; color: var(--muted); }
        @media (max-width: 820px) { .auth-card { grid-template-columns: 1fr; } .auth-aside { display: none; } }
    </style>
@endpush

@section('content')
    <div class="auth-shell">
        <div class="auth-card">
            <aside class="auth-aside">
                <img class="auth-logo" src="{{ asset('images/logo.svg') }}" alt="AReport">
                <span class="eyebrow">Create your workspace</span>
                <h1>Start reporting with AReport</h1>
                <p>One account to model taxonomies, enter submission data, validate against the rules, and export compliant xBRL.</p>
                <ul>
                    <li><i class="fas fa-check"></i> EBA DPM 1.0 &amp; 2.0 frameworks out of the box</li>
                    <li><i class="fas fa-check"></i> DPM Architect for authoring your own model</li>
                    <li><i class="fas fa-check"></i> DPM-XL validation with cell-level feedback</li>
                    <li><i class="fas fa-check"></i> Import &amp; export xBRL-XML, OIM CSV and JSON</li>
                </ul>
            </aside>

            <div class="auth-body">
                <h2>Create an account</h2>
                <p class="sub">It takes less than a minute.</p>

                @include('flash.flash-message')

                <form method="POST" action="{{ route('register') }}" class="auth-form">
                    @csrf

                    <div>
                        <label for="name" class="form-label">Full name</label>
                        <input id="name" type="text" class="form-control @error('name') is-invalid @enderror"
                               name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>
                        @error('name')<span class="invalid-feedback d-block"><strong>{{ $message }}</strong></span>@enderror
                    </div>

                    <div>
                        <label for="email" class="form-label">Email address</label>
                        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                               name="email" value="{{ old('email') }}" required autocomplete="email">
                        @error('email')<span class="invalid-feedback d-block"><strong>{{ $message }}</strong></span>@enderror
                    </div>

                    <div>
                        <label for="password" class="form-label">Password</label>
                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror"
                               name="password" required autocomplete="new-password">
                        @error('password')<span class="invalid-feedback d-block"><strong>{{ $message }}</strong></span>@enderror
                    </div>

                    <div>
                        <label for="password-confirm" class="form-label">Confirm password</label>
                        <input id="password-confirm" type="password" class="form-control"
                               name="password_confirmation" required autocomplete="new-password">
                    </div>

                    <button type="submit" class="btn btn-primary w-100 mt-1">Create account</button>
                </form>

                <div class="auth-foot">
                    Already have an account? <a href="{{ route('login') }}">Sign in</a>
                </div>
            </div>
        </div>
    </div>
@endsection
