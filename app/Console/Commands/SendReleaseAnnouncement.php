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
                            {--limit=0 : Limit the number of users when using --all}
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
        $dryRun = (bool) $this->option('dry-run');
        $limit = max((int) $this->option('limit'), 0);

        if ($explicitEmails->isEmpty() && !$sendToAll) {
            $this->error('Choose at least one target: use --email=... or --all.');

            return self::FAILURE;
        }

        $recipients = collect();

        if ($sendToAll) {
            $query = User::query()
                ->select(['name', 'email'])
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->orderBy('id');

            if ($limit > 0) {
                $query->limit($limit);
            }

            $recipients = $recipients->merge($query->get()->map(function (User $user) {
                return [
                    'email' => $user->email,
                    'name' => $user->name,
                    'source' => 'users',
                ];
            }));
        }

        if ($explicitEmails->isNotEmpty()) {
            $recipients = $recipients->merge($explicitEmails->map(function (string $email) {
                return [
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
            ['Email', 'Name', 'Source'],
            $recipients->map(fn (array $recipient) => [
                $recipient['email'],
                $recipient['name'] ?: '-',
                $recipient['source'],
            ])->all()
        );

        if ($dryRun) {
            $this->comment('Dry run completed. No email was sent.');

            return self::SUCCESS;
        }

        foreach ($recipients as $recipient) {
            Mail::to($recipient['email'])->send(
                new ReleaseAnnouncementMail($recipient['name'], $subject)
            );
        }

        $this->info(sprintf('Release announcement sent to %d recipient(s).', $recipients->count()));

        return self::SUCCESS;
    }
}
