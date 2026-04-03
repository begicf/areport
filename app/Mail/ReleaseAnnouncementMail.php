<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReleaseAnnouncementMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $recipientName;
    public string $subjectLine;
    public string $loginUrl;
    public array $highlights;

    public function __construct(?string $recipientName = null, ?string $subjectLine = null, array $highlights = [])
    {
        $this->recipientName = trim((string) $recipientName);
        $this->subjectLine = $subjectLine ?: 'AReport open-source update: DPM 1.0 and DPM 2.0 support is now available';
        $this->loginUrl = url('/login');
        $this->highlights = $highlights ?: [
            'DPM 1.0 and DPM 2.0 reporting workflows are now supported.',
            'xBRL XML and xBRL-CSV export flows were improved.',
            'The reporting workspace and taxonomy navigation were updated.',
        ];
    }

    public function build(): self
    {
        return $this
            ->subject($this->subjectLine)
            ->view('emails.release-announcement');
    }
}
