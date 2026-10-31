<?php

namespace App\Http\Controllers\Contact;

use App\Http\Controllers\Controller;
use App\Model\ContactMessage;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

/**
 * The public "contact the author" form. The author's address is never on the page;
 * messages are stored and mailed to config('areport.contact.to'). Spam defences,
 * all invisible to people: a honeypot field, a signed form token that must be at
 * least a few seconds old (and not stale), a per-IP rate limit on the route, and
 * a cap on links. A trapped submission gets the same thank-you as a real one.
 */
class ContactController extends Controller
{
    /** Signed timestamp for the form; the page embeds it when rendered. */
    public static function formToken(): string
    {
        return Crypt::encryptString((string) time());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'organisation' => ['nullable', 'string', 'max:190'],
            'topic' => ['required', Rule::in(array_keys(ContactMessage::TOPICS))],
            'message' => ['required', 'string', 'min:10', 'max:4000'],
        ]);

        if ($this->looksLikeSpam($request, $data['message'])) {
            Log::info('Contact form: submission trapped as spam', ['ip' => $request->ip()]);

            return $this->thanks($request);
        }

        $msg = ContactMessage::create($data + [
            'ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
        ]);

        $to = config('areport.contact.to');
        if ($to) {
            try {
                Mail::raw($this->mailBody($msg), function ($m) use ($to, $msg) {
                    $m->to($to)->replyTo($msg->email, $msg->name)
                        ->subject('[AReport] '.ContactMessage::TOPICS[$msg->topic].' — '.$msg->name);
                });
                $msg->update(['mailed' => true]);
            } catch (\Throwable $e) {
                Log::warning('Contact form: mail not sent', ['id' => $msg->id, 'error' => $e->getMessage()]);
            }
        }

        return $this->thanks($request);
    }

    private function looksLikeSpam(Request $request, string $message): bool
    {
        if (filled($request->input('website'))) {   // honeypot: hidden from people, filled by bots
            return true;
        }
        try {
            $age = time() - (int) Crypt::decryptString((string) $request->input('token'));
        } catch (DecryptException) {
            return true;
        }
        if ($age < (int) config('areport.contact.min_seconds', 4) || $age > 86400) {
            return true;
        }

        return preg_match_all('#https?://|www\.#i', $message) > (int) config('areport.contact.max_links', 2);
    }

    private function mailBody(ContactMessage $m): string
    {
        return "Topic: ".ContactMessage::TOPICS[$m->topic]."\n"
            ."Name: {$m->name}\nEmail: {$m->email}\n"
            .($m->organisation ? "Organisation: {$m->organisation}\n" : '')
            ."\n{$m->message}\n\n— sent from the AReport contact form (#{$m->id}, {$m->ip})";
    }

    private function thanks(Request $request)
    {
        $text = 'Thank you — your message has been sent. The author will reply by email.';

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'message' => $text])
            : redirect(url('/').'#contact')->with('contact_status', $text);
    }
}
