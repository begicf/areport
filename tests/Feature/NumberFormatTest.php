<?php

namespace Tests\Feature;

use App\Support\NumberFormat;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Settings → Number format: how a user's numbers are shown and read. */
class NumberFormatTest extends TestCase
{
    use RefreshDatabase;

    private function user(?string $style = null): User
    {
        $user = User::query()->create(['name' => 'Alice', 'email' => 'alice@example.com', 'password' => Hash::make('secret'), 'email_verified_at' => now()]);
        $user->forceFill(['number_format' => $style])->save();
        $this->actingAs($user);

        return $user;
    }

    public function test_numbers_follow_the_users_style(): void
    {
        $user = $this->user();
        $this->assertSame('1,250,000.5', NumberFormat::format('1250000.5'));   // default

        $user->forceFill(['number_format' => 'eu'])->save();
        $this->assertSame('1.250.000,5', NumberFormat::format('1250000.5'));
        $this->assertSame('-1.234', NumberFormat::format(-1234));
        $this->assertSame('0,125', NumberFormat::format('0.125'));
        $this->assertSame('eba_CU:EUR', NumberFormat::format('eba_CU:EUR'));   // not a number
        $this->assertSame(['style' => 'eu', 'decimal' => ',', 'group' => '.'], NumberFormat::js());
    }

    public function test_a_user_chooses_a_style_in_settings(): void
    {
        $user = $this->user();
        $this->get('/account/settings')->assertOk()->assertSee('1.234.567,89')->assertSee('window.AREPORT_NUM', false);

        $this->post('/account/settings', ['number_format' => 'space'])->assertRedirect('/account/settings');
        $this->assertSame('space', $user->fresh()->number_format);
        $this->assertSame('1 234,5', NumberFormat::format('1234.5'));

        $this->post('/account/settings', ['number_format' => 'xx'])->assertSessionHasErrors('number_format');
        $this->assertSame('space', $user->fresh()->number_format);
    }
}
