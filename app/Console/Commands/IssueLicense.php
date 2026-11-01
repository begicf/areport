<?php

namespace App\Console\Commands;

use App\Support\License;
use Illuminate\Console\Command;

/**
 * Issue a signed license, or show the configured one. Issuing needs the private signing
 * key, which never leaves the issuer's machine (default ~/.areport/license-signing.key) —
 * so only the issuer can do it.
 */
class IssueLicense extends Command
{
    protected $signature = 'areport:license
        {licensee? : Who the license is for (omit to show the configured license)}
        {--product=* : Product(s) it includes: validation, teams, architect, or all}
        {--domain=* : Host(s) it covers, e.g. reporting.bank.eu, *.bank.eu}
        {--expires= : Last valid day (YYYY-MM-DD); empty = no expiry}
        {--key-file= : Private signing key (base64), default ~/.areport/license-signing.key}';

    protected $description = 'Issue a signed AReport license key, or show the configured one';

    public function handle(): int
    {
        if ($this->argument('licensee') === null) {
            return $this->status();
        }

        $file = $this->option('key-file') ?: rtrim((string) getenv('HOME'), '/').'/.areport/license-signing.key';
        $secret = is_file($file) ? base64_decode(trim((string) file_get_contents($file)), true) : false;
        if (! $secret || strlen($secret) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
            $this->error("No private signing key at {$file}.");

            return self::FAILURE;
        }
        if (base64_encode(sodium_crypto_sign_publickey_from_secretkey($secret)) !== License::PUBLIC_KEY) {
            $this->error('This signing key does not match the public key built into AReport.');

            return self::FAILURE;
        }
        $products = array_values(array_unique(array_filter(array_map(fn ($p) => strtolower(trim($p)), (array) $this->option('product')))));
        if ($products === ['all']) {
            $products = array_keys(License::PRODUCTS);
        }
        if (! $products || array_diff($products, array_keys(License::PRODUCTS))) {
            $this->error('Give --product: '.implode(', ', array_keys(License::PRODUCTS)).', or all.');

            return self::FAILURE;
        }
        $domains = array_values(array_filter(array_map('trim', (array) $this->option('domain'))));
        if (! $domains) {
            $this->error('Give at least one --domain.');

            return self::FAILURE;
        }
        $expires = $this->option('expires') ?: null;
        if ($expires && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $expires)) {
            $this->error('--expires must be YYYY-MM-DD.');

            return self::FAILURE;
        }

        $key = License::issue(['licensee' => (string) $this->argument('licensee'), 'products' => $products, 'domains' => $domains, 'expires' => $expires], $secret);
        $this->line($key);
        $this->info('Set it as AREPORT_LICENSE_KEY (and AREPORT_LICENSE_ENFORCEMENT=true) in the licensee\'s .env.');

        return self::SUCCESS;
    }

    private function status(): int
    {
        $license = License::current();
        if (! $license['valid']) {
            $this->warn($license['reason']);
        } else {
            $this->line('Licensed to '.$license['licensee'].' · '.implode(', ', $license['domains']).' · '.($license['expires'] ? 'valid until '.$license['expires'] : 'no expiry'));
        }
        foreach (License::PRODUCTS as $product => $label) {
            $this->line(sprintf('  %-11s %s', $product, in_array($product, $license['products'], true) ? 'included' : '—').'  '.$label);
        }
        $this->line('Enforcement '.(License::enforced() ? 'on' : 'off').'.');

        return $license['valid'] || ! License::enforced() ? self::SUCCESS : self::FAILURE;
    }
}
