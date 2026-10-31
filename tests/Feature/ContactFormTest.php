<?php

namespace Tests\Feature;

use App\Http\Controllers\Contact\ContactController;
use App\Model\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        config(['areport.contact.to' => 'author@example.test']);
    }

    private function payload(array $over = []): array
    {
        return array_merge([
            'name' => 'Ana Analyst',
            'email' => 'ana@bank.example',
            'organisation' => 'Demo Banka',
            'topic' => 'plugins',
            'message' => 'We would like to try DPM Architect for our supervisor.',
            'website' => '',
            'token' => Crypt::encryptString((string) (time() - 30)),   // filled in 30 s
        ], $over);
    }

    public function testPageShowsFormWithoutTheAuthorsAddress(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('id="contactForm"', $html);
        $this->assertStringContainsString('name="website"', $html);
        $this->assertStringNotContainsString('author@example.test', $html);
    }

    public function testRealMessageIsStoredAndMailed(): void
    {
        $this->postJson('/contact', $this->payload())->assertOk()->assertJson(['ok' => true]);
        $msg = ContactMessage::query()->sole();
        $this->assertSame('ana@bank.example', $msg->email);
        $this->assertTrue($msg->mailed);
    }

    public function testHoneypotTooFastAndLinkSpamAreDroppedSilently(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $this->postJson('/contact', $this->payload(['website' => 'http://spam.example']))->assertOk();
        $this->postJson('/contact', $this->payload(['token' => ContactController::formToken()]))->assertOk();   // 0 s old
        $this->postJson('/contact', $this->payload(['token' => 'forged']))->assertOk();
        $this->postJson('/contact', $this->payload(['message' => 'Buy http://a.example http://b.example http://c.example now']))->assertOk();
        $this->assertSame(0, ContactMessage::query()->count());
    }

    public function testValidationAndRateLimit(): void
    {
        $this->postJson('/contact', $this->payload(['email' => 'not-an-email']))->assertStatus(422);
        $this->postJson('/contact', $this->payload())->assertOk();
        $this->postJson('/contact', $this->payload())->assertOk();
        $this->postJson('/contact', $this->payload())->assertStatus(429);   // 3 per 10 minutes per IP
    }
}
