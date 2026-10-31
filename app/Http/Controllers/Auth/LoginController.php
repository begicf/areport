<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * A correct password is not enough: until the email address is verified the user
     * is signed straight out again and (at most every 5 minutes) sent a verification link.
     */
    protected function authenticated(Request $request, $user)
    {
        if (! $user instanceof MustVerifyEmail || $user->hasVerifiedEmail()) {
            return null;
        }

        $key = 'verification-mail:'.$user->getKey();
        if (! RateLimiter::tooManyAttempts($key, 1)) {
            RateLimiter::hit($key, 300);
            $user->sendEmailVerificationNotification();
        }

        $this->guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->withInput($request->only($this->username()))
            ->with('warning', 'Please verify your email address first. We have sent a verification link to '
                .$user->getEmailForVerification().'; open it, then sign in.');
    }
}
