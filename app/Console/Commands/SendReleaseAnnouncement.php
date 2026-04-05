<?php

namespace App\Console\Commands;

use App\Mail\ReleaseAnnouncementMail;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendReleaseAnnouncement extends Command
{
    protected $signature = 'release:announce
                            {--email=* : Send the announcement to one or more explicit email addresses}
                            {--all : Send the announcement to all registered users}
                            {--after-id=0 : Resume from users with an ID greater than this value when using --all}
                            {--limit=0 : Limit the number of users when using --all}
                            {--sleep-ms=0 : Pause between emails in milliseconds}
                            {--dry-run : Show the recipients without sending any email}
                            {--subject= : Override the email subject line}';

    protected $description = 'Send the new release announcement to registered users';

    public function handle(): int
    {
        $subject = $this->option('subject') ?: 'AReport open-source update: DPM 1.0 and DPM 2.0 support is now available';
        $explicitEmails = collect((array) $this->option('email'))
            ->map(fn ($email) => trim((string) $email))
            ->filter()
            ->unique()
            ->values();

        $sendToAll = (bool) $this->option('all');
        $afterId = max((int) $this->option('after-id'), 0);
        $dryRun = (bool) $this->option('dry-run');
        $limit = max((int) $this->option('limit'), 0);
        $sleepMs = max((int) $this->option('sleep-ms'), 0);

        if ($explicitEmails->isEmpty() && !$sendToAll) {
            $this->error('Choose at least one target: use --email=... or --all.');

            return self::FAILURE;
        }

        $recipients = collect();

        if ($sendToAll) {
            $query = User::query()
                ->select(['id', 'name', 'email'])
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->where('id', '>', $afterId)
                ->orderBy('id');

            if ($limit > 0) {
                $query->limit($limit);
            }

            $recipients = $recipients->merge($query->get()->map(function (User $user) {
                return [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'source' => 'users',
                ];
            }));
        }

        if ($explicitEmails->isNotEmpty()) {
            $recipients = $recipients->merge($explicitEmails->map(function (string $email) {
                return [
                    'id' => null,
                    'email' => $email,
                    'name' => null,
                    'source' => 'manual',
                ];
            }));
        }

        $recipients = $recipients
            ->unique('email')
            ->values();

        if ($recipients->isEmpty()) {
            $this->warn('No recipients were resolved.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Resolved %d recipient(s).', $recipients->count()));

        $this->table(
            ['ID', 'Email', 'Name', 'Source'],
            $recipients->map(fn (array $recipient) => [
                $recipient['id'] ?: '-',
                $recipient['email'],
                $recipient['name'] ?: '-',
                $recipient['source'],
            ])->all()
        );

        if ($dryRun) {
            $this->comment('Dry run completed. No email was sent.');

            return self::SUCCESS;
        }

        $sentCount = 0;
        $lastSentId = $afterId;
        $recipientCount = $recipients->count();

        foreach ($recipients as $index => $recipient) {
            try {
                Mail::to($recipient['email'])->send(
                    new ReleaseAnnouncementMail($recipient['name'], $subject)
                );
            } catch (\Throwable $exception) {
                $this->error(sprintf(
                    'Sending stopped after %d recipient(s): %s',
                    $sentCount,
                    $exception->getMessage()
                ));

                if ($sendToAll) {
                    $resumeCommand = sprintf(
                        'php artisan release:announce --all --after-id=%d%s%s',
                        $lastSentId,
                        $limit > 0 ? sprintf(' --limit=%d', $limit) : '',
                        $sleepMs > 0 ? sprintf(' --sleep-ms=%d', $sleepMs) : ''
                    );

                    $this->warn(sprintf('Resume with: %s', $resumeCommand));
                }

                return self::FAILURE;
            }

            $sentCount++;

            if (!empty($recipient['id'])) {
                $lastSentId = (int) $recipient['id'];
            }

            if ($sleepMs > 0 && $index < ($recipientCount - 1)) {
                usleep($sleepMs * 1000);
            }
        }

        $this->info(sprintf('Release announcement sent to %d recipient(s).', $sentCount));

        return self::SUCCESS;
    }
}
