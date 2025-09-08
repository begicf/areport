<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'AREPORT') }}</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">

        <!-- Styles -->
        <style>
            html, body {
                background-color: #fff;
                color: #636b6f;
                font-family: 'Nunito', sans-serif;
                font-weight: 200;
                height: 100vh;
                margin: 0;
            }

            .full-height {
                height: 100vh;
            }

            .flex-center {
                align-items: center;
                display: flex;
                justify-content: center;
            }

            .position-ref {
                position: relative;
            }

            .top-right {
                position: absolute;
                right: 10px;
                top: 18px;
            }

            .content {
                text-align: center;
            }

            .title {
                font-size: 84px;
            }

            .links > a {
                color: #636b6f;
                padding: 0 25px;
                font-size: 13px;
                font-weight: 600;
                letter-spacing: .1rem;
                text-decoration: none;
                text-transform: uppercase;
            }

            .m-b-md {
                margin-bottom: 30px;
            }
        </style>

        <!-- Styles -->
        <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    </head>
    <body>
        <div class="flex-center position-ref full-height">
            @if (Route::has('login'))
                <div class="top-right links">
                    @auth
                        <a href="{{ url('/home') }}">Home</a>
                        <a  target="_blank" href="{{ url('https://github.com/begicf/areport') }}"><i class="fab fa-github"></i>GitHub</a>
                    @else

                        <a href="{{ route('login') }}">Login</a>

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}">Register</a>
                        @endif

                        <a target="_blank" href="{{ url('https://github.com/begicf/areport') }}"><i class="fab fa-github"></i>GitHub</a>
                    @endauth
                </div>
            @endif

            <div class="content">

                <div class="title m-b-md">

                    <img width="225px" src="{{asset('images/logo.svg')}}" alt="areport">

                </div>

                <div id="bp-payment-button"></div>

                <script src="https://cdn.jsdelivr.net/npm/@confirmo/overlay@latest/dist/confirmo.js"></script>
                <script type="text/javascript">
                    Confirmo.PaymentButton.initialize({
                        "id": "bp-payment-button",
                        "url": "https://confirmo.net",
                        "buttonType": "DONATION",
                        "paymentButtonId": "WqgEkOPl7RZzwD026dmgJ1864QL3xXBa9Ny",
                        "values": {
                            "merchantAmount": null,
                            "merchantCurrency": null,
                            "productName": "areport.net",
                            "productDescription": null,
                            "reference": null,
                            "returnUrl": null,
                            "merchantId": "mer2dqkr9vdp",
                            "overlay": true
                        }
                    });
                </script>



            </div>
        </div>
    </body>
</html>
