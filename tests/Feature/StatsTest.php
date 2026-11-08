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

    public function test_the_counts_are_read_with_the_token_only(): void
    {
        $this->user('ana@bank.example');
        User::query()->create(['name' => 'Bo', 'email' => 'bo@bank.example', 'password' => Hash::make('secret')]);
        Stats::hit('exports', 4);

        config(['areport.stats.token' => null]);
        $this->getJson('/stats.json')->assertNotFound();
        $this->getJson('/stats.json', ['Authorization' => 'Bearer '])->assertNotFound();

        config(['areport.stats.token' => 'secret-token']);
        $this->getJson('/stats.json', ['Authorization' => 'Bearer wrong'])->assertNotFound();
        $json = $this->getJson('/stats.json?days=7', ['Authorization' => 'Bearer secret-token'])->assertOk()->json();
        $this->assertCount(7, $json['days']);
        $this->assertSame(4, $json['days'][now()->toDateString()]['exports']);
        $this->assertSame('Exports', $json['metrics']['exports']);
        $this->assertSame(['total' => 2, 'verified' => 1], $json['users']);

        $this->get('/stats')->assertNotFound();   // no usage page in the workspace
        $this->artisan('areport:stats', ['--days' => 3])->assertSuccessful();
    }
}
