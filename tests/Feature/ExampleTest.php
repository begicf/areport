<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** The workspace has no public page: guests sign in, and search engines are kept out. */
class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function testGuestsAreSentToSignIn(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/login')->assertOk();
    }

    public function testSignedInUsersGoToTheirWorkspace(): void
    {
        $this->actingAs(User::query()->create(['name' => 'Alice', 'email' => 'alice@example.com', 'password' => Hash::make('secret'), 'email_verified_at' => now()]));

        $this->get('/')->assertRedirect('/home');
    }

    public function testSearchEnginesAreKeptOut(): void
    {
        $this->get('/robots.txt')->assertOk()->assertSee("Disallow: /\n", false);
        $this->get('/sitemap.xml')->assertNotFound();
    }
}
