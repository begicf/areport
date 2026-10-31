<?php

namespace Tests\Unit;

use Begicf\AReportPro\Support\License;
use PHPUnit\Framework\TestCase;

class LicenseTest extends TestCase
{
    private string $secret;
    private string $public;

    protected function setUp(): void
    {
        parent::setUp();
        if (! class_exists(License::class)) {
            $this->markTestSkipped('areport-pro is not installed.');
        }
        $kp = sodium_crypto_sign_keypair();
        $this->secret = sodium_crypto_sign_secretkey($kp);
        $this->public = base64_encode(sodium_crypto_sign_publickey($kp));
    }

    private function key(array $over = []): string
    {
        return License::issue($over + ['licensee' => 'Example Bank', 'domains' => ['reporting.bank.eu', '*.test.bank.eu'], 'expires' => '2099-12-31'], $this->secret);
    }

    public function test_a_signed_license_covers_its_hosts(): void
    {
        $r = License::check($this->key(), 'reporting.bank.eu', $this->public);
        $this->assertTrue($r['valid']);
        $this->assertSame('Example Bank', $r['licensee']);
        $this->assertTrue(License::check($this->key(), 'uat.test.bank.eu', $this->public)['valid']);
        $this->assertFalse(License::check($this->key(), 'other.example.com', $this->public)['valid']);
    }

    public function test_expired_tampered_and_foreign_keys_are_refused(): void
    {
        $this->assertStringContainsString('expired', License::check($this->key(['expires' => '2020-01-01']), 'reporting.bank.eu', $this->public)['reason']);

        // Editing the payload (e.g. adding a domain) breaks the signature.
        [$p, $body, $sig] = explode('.', $this->key());
        $forged = rtrim(strtr(base64_encode(json_encode(['licensee' => 'Example Bank', 'domains' => ['*'], 'expires' => null])), '+/', '-_'), '=');
        $this->assertFalse(License::check("$p.$forged.$sig", 'any.host', $this->public)['valid']);

        // A key signed by someone else does not verify against the built-in public key.
        $this->assertFalse(License::check($this->key(), 'reporting.bank.eu')['valid']);
        $this->assertFalse(License::check('anything', 'reporting.bank.eu')['valid']);
        $this->assertFalse(License::check('', 'reporting.bank.eu')['valid']);
    }
}
