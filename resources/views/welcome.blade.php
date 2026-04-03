<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'AReport') }}</title>

    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

    <style>
        .welcome-shell {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .welcome-card {
            width: min(1120px, 100%);
            border-radius: 28px;
            overflow: hidden;
            border: 1px solid rgba(15, 23, 42, 0.08);
            box-shadow: 0 24px 54px rgba(15, 23, 42, 0.12);
            background: #fff;
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(0, 0.95fr);
        }

        .welcome-hero {
            padding: 2.25rem;
            background:
                radial-gradient(circle at top left, rgba(255, 255, 255, 0.2), transparent 28%),
                linear-gradient(135deg, #081122 0%, #163b75 52%, #0e7490 100%);
            color: #fff;
        }

        .welcome-hero .app-page-kicker {
            color: rgba(255, 255, 255, 0.88);
        }

        .welcome-hero .app-page-title,
        .welcome-hero .app-page-copy {
            color: #fff;
        }

        .welcome-side {
            padding: 2.25rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 1.5rem;
        }

        .welcome-links {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 0.75rem;
        }

        .welcome-links a {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.7rem 1rem;
            border-radius: 999px;
            font-weight: 700;
            color: #0f172a;
            background: #f8fafc;
            border: 1px solid rgba(15, 23, 42, 0.08);
        }

        .welcome-links a:hover,
        .welcome-links a:focus {
            color: #0f172a;
            background: #eef5ff;
        }

        .welcome-logo {
            width: 220px;
            max-width: 100%;
        }

        @media (max-width: 991.98px) {
            .welcome-card {
                grid-template-columns: 1fr;
            }

            .welcome-hero,
            .welcome-side {
                padding: 1.35rem;
            }

            .welcome-links {
                justify-content: flex-start;
            }
        }
    </style>
</head>
<body>
<div class="welcome-shell">
    <div class="welcome-card">
        <section class="welcome-hero">
            <div class="app-page-kicker">
                <i class="fas fa-chart-line"></i>
                Financial reporting
            </div>
            <h1 class="app-page-title">Structured reporting for taxonomy-driven workflows</h1>
            <p class="app-page-copy">
                AReport helps teams manage taxonomies, navigate reporting modules, and maintain financial submission data in one focused workspace.
            </p>
        </section>

        <section class="welcome-side">
            @if (Route::has('login'))
                <div class="welcome-links">
                    @auth
                        <a href="{{ url('/home') }}">Open workspace</a>
                    @else
                        <a href="{{ route('login') }}">Sign in</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}">Register</a>
                        @endif
                    @endauth
                    <a target="_blank" href="{{ url('https://github.com/begicf/areport') }}">
                        <i class="fab fa-github"></i>
                        GitHub
                    </a>
                </div>
            @endif

            <div>
                <img class="welcome-logo" src="{{ asset('images/logo.svg') }}" alt="AReport">
                <p class="app-form-help mt-4 mb-0">
                    Support ongoing development if this project helps your reporting workflow.
                </p>
            </div>

            <div id="bp-payment-button"></div>
        </section>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@confirmo/overlay@latest/dist/confirmo.js"></script>
<script type="text/javascript">
    Confirmo.PaymentButton.initialize({
        id: 'bp-payment-button',
        url: 'https://confirmo.net',
        buttonType: 'DONATION',
        paymentButtonId: 'WqgEkOPl7RZzwD026dmgJ1864QL3xXBa9Ny',
        values: {
            merchantAmount: null,
            merchantCurrency: null,
            productName: 'areport.net',
            productDescription: null,
            reference: null,
            returnUrl: null,
            merchantId: 'mer2dqkr9vdp',
            overlay: true
        }
    });
</script>
</body>
</html>
