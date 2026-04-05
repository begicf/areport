<?php

namespace Tests\Feature;

use App\Mail\ReleaseAnnouncementMail;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class SendReleaseAnnouncementCommandTest extends TestCase
{
    use RefreshDatabase;

    public function testDryRunResolvesUsersWithoutSendingMail(): void
    {
        User::query()->create([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => Hash::make('password'),
        ]);

        Mail::fake();

        $this->artisan('release:announce', [
            '--all' => true,
            '--dry-run' => true,
        ])
            ->expectsOutput('Resolved 1 recipient(s).')
            ->expectsOutput('Dry run completed. No email was sent.')
            ->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function testCommandCanSendToExplicitEmailAddress(): void
    {
        Mail::fake();

        $this->artisan('release:announce', [
            '--email' => ['test@example.com'],
        ])
            ->expectsOutput('Resolved 1 recipient(s).')
            ->expectsOutput('Release announcement sent to 1 recipient(s).')
            ->assertExitCode(0);

        Mail::assertSent(ReleaseAnnouncementMail::class, function (ReleaseAnnouncementMail $mail) {
            return $mail->hasTo('test@example.com')
                && $mail->subjectLine === 'AReport open-source update: DPM 1.0 and DPM 2.0 support is now available';
        });
    }

    public function testCommandCanSendToAllUsersWithLimit(): void
    {
        User::query()->create([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => Hash::make('password'),
        ]);

        User::query()->create([
            'name' => 'Bob',
            'email' => 'bob@example.com',
            'password' => Hash::make('password'),
        ]);

        Mail::fake();

        $this->artisan('release:announce', [
            '--all' => true,
            '--limit' => 1,
        ])
            ->expectsOutput('Resolved 1 recipient(s).')
            ->expectsOutput('Release announcement sent to 1 recipient(s).')
            ->assertExitCode(0);

        Mail::assertSent(ReleaseAnnouncementMail::class, 1);
    }

    public function testDryRunSupportsResumingAfterUserId(): void
    {
        User::query()->create([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => Hash::make('password'),
        ]);

        User::query()->create([
            'name' => 'Bob',
            'email' => 'bob@example.com',
            'password' => Hash::make('password'),
        ]);

        Mail::fake();

        $this->artisan('release:announce', [
            '--all' => true,
            '--after-id' => 1,
            '--limit' => 1,
            '--dry-run' => true,
        ])
            ->expectsOutput('Resolved 1 recipient(s).')
            ->expectsTable(
                ['ID', 'Email', 'Name', 'Source'],
                [[2, 'bob@example.com', 'Bob', 'users']]
            )
            ->expectsOutput('Dry run completed. No email was sent.')
            ->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function testCommandPrintsResumeHintWhenSendingFails(): void
    {
        User::query()->create([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => Hash::make('password'),
        ]);

        User::query()->create([
            'name' => 'Bob',
            'email' => 'bob@example.com',
            'password' => Hash::make('password'),
        ]);

        $pendingMail = new class {
            public function send($mailable): void
            {
                throw new RuntimeException('SMTP limit');
            }
        };

        Mail::shouldReceive('to')
            ->once()
            ->with('alice@example.com')
            ->andReturn($pendingMail);

        $this->artisan('release:announce', [
            '--all' => true,
            '--limit' => 2,
            '--sleep-ms' => 250,
        ])
            ->expectsOutput('Resolved 2 recipient(s).')
            ->expectsOutput('Sending stopped after 0 recipient(s): SMTP limit')
            ->expectsOutput('Resume with: php artisan release:announce --all --after-id=0 --limit=2 --sleep-ms=250')
            ->assertExitCode(1);
    }
}
