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

    public function test_a_signed_in_user_counts_once_a_day(): void
    {
        $ana = $this->user('ana@bank.example');
        $this->actingAs($ana)->get('/home');
        $this->actingAs($ana)->get('/entities');
        $this->actingAs($this->user('bo@bank.example'))->get('/home');
        $this->assertSame(2, Stats::days(1)[now()->toDateString()]['active_users']);
    }

    public function test_only_the_listed_viewers_see_the_usage_page(): void
    {
        config(['areport.stats.viewers' => ['boss@bank.example']]);
        $this->actingAs($this->user('ana@bank.example'))->get('/stats')->assertNotFound();
        $this->actingAs($this->user('boss@bank.example'))->get('/stats')->assertOk()->assertSee('Usage')->assertSee('Signed-in users');
        $this->artisan('areport:stats', ['--days' => 3])->assertSuccessful();
    }
}
