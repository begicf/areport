<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationLoginTest extends TestCase
{
    use RefreshDatabase;

    private function user(bool $verified): User
    {
        $user = User::query()->create(['name' => 'Alice', 'email' => 'alice@example.com', 'password' => Hash::make('secret123')]);
        if ($verified) {
            $user->markEmailAsVerified();
        }

        return $user;
    }

    public function testAnUnverifiedUserIsNotSignedInAndGetsOneVerificationMail(): void
    {
        Notification::fake();
        $user = $this->user(false);

        $this->post('/login', ['email' => $user->email, 'password' => 'secret123'])
            ->assertRedirect('/login')
            ->assertSessionHas('warning');
        $this->assertGuest();

        // A second attempt right away does not send another mail.
        $this->post('/login', ['email' => $user->email, 'password' => 'secret123'])->assertRedirect('/login');
        $this->assertGuest();

        Notification::assertSentToTimes($user, VerifyEmail::class, 1);
    }

    public function testAWrongPasswordSendsNoMail(): void
    {
        Notification::fake();
        $user = $this->user(false);

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    }

    public function testAVerifiedUserSignsIn(): void
    {
        $user = $this->user(true);

        $this->post('/login', ['email' => $user->email, 'password' => 'secret123'])->assertRedirect('/home');
        $this->assertAuthenticatedAs($user);
    }

    public function testTheSignedLinkVerifiesWithoutSigningIn(): void
    {
        $user = $this->user(false);
        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1($user->email)]);

        $this->get($url)->assertRedirect('/login')->assertSessionHas('success');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertGuest();
    }

    public function testATamperedLinkIsRejected(): void
    {
        $user = $this->user(false);
        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1('other@example.com')]);

        $this->get($url)->assertForbidden();
        $this->get('/email/verify/'.$user->id.'/'.sha1($user->email))->assertForbidden();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function testRegistrationMailsALinkAndDoesNotSignIn(): void
    {
        Notification::fake();

        $this->post('/register', ['name' => 'Bob', 'email' => 'bob@example.com', 'password' => 'secret123', 'password_confirmation' => 'secret123'])
            ->assertRedirect('/login')
            ->assertSessionHas('info');
        $this->assertGuest();

        Notification::assertSentTo(User::query()->where('email', 'bob@example.com')->first(), VerifyEmail::class);
    }

    public function testAPasswordResetVerifiesTheEmail(): void
    {
        $user = $this->user(false);
        $token = Password::broker()->createToken($user);

        $this->post('/password/reset', ['token' => $token, 'email' => $user->email, 'password' => 'newsecret123', 'password_confirmation' => 'newsecret123'])
            ->assertRedirect('/home');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertAuthenticatedAs($user);
    }
}
