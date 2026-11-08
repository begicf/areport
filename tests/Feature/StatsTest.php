<?php

namespace Tests\Feature;

use App\Support\Stats;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StatsTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email): User
    {
        $user = User::query()->create(['name' => 'Ana', 'email' => $email, 'password' => Hash::make('secret')]);
        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }

    public function test_counts_are_kept_per_day_without_who(): void
    {
        Stats::hit('validations');
        Stats::hit('validations', 2);
        $this->assertSame(3, Stats::days(1)[now()->toDateString()]['validations']);
        $this->assertSame(['id', 'day', 'metric', 'value'], array_keys((array) DB::table('daily_stats')->first()));
    }

    public function test_a_sign_in_is_remembered_on_the_user(): void
    {
        $ana = $this->user('ana@bank.example');
        $updated = $ana->updated_at;
        $this->travel(5)->minutes();

        $this->post('/login', ['email' => 'ana@bank.example', 'password' => 'secret'])->assertRedirect();

        $ana->refresh();
        $this->assertSame(now()->format('Y-m-d H:i'), $ana->last_login_at?->format('Y-m-d H:i'));
        $this->assertEquals($updated, $ana->updated_at);
        $this->assertSame(1, Stats::days(1)[now()->toDateString()]['logins']);
    }

    public function test_a_signed_in_user_counts_once_a_day(): void
    {
        $ana = $this->user('ana@bank.example');
        $this->actingAs($ana)->get('/home');
        $this->actingAs($ana)->get('/entities');
        $this->actingAs($this->user('bo@bank.example'))->get('/home');
        $this->assertSame(2, Stats::days(1)[now()->toDateString()]['active_users']);
    }

    public function test_the_counts_are_shown_on_the_command_line_only(): void
    {
        Stats::hit('exports', 4);

        $this->get('/stats')->assertNotFound();
        $this->get('/stats.json')->assertNotFound();
        $this->artisan('areport:stats', ['--days' => 3])->assertSuccessful();
    }
}
