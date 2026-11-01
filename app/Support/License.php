<?php

namespace App\Support;

/**
 * Signed licenses of the AReport plugins: AREPORT1.<payload>.<signature>, base64url, where
 * the payload is JSON {licensee, products[], domains[], expires, issued} and the signature
 * an Ed25519 signature over "AREPORT1.<payload>". Only the holder of the private signing
 * key (kept off the repository, see the areport:license command) can issue one; the public
 * key below verifies it, so a key cannot be forged or edited (products, domains, expiry).
 *
 * One key licenses any of the products: validation (AReport DPM), teams (AReport Teams),
 * architect (AReport Pro). A key issued before products existed covers what AReport Pro
 * held then: the Architect and validation.
 */
class License
{
    public const PREFIX = 'AREPORT1';

    /** Ed25519 public key of the AReport license issuer. */
    public const PUBLIC_KEY = 'tj2n3d7AJVATqub3cZxS9OW1wYO4Y5zaxBGseNckn9Q=';

    public const PRODUCTS = [
        'validation' => 'Validation (AReport DPM)',
        'teams' => 'Teams and sign-off (AReport Teams)',
        'architect' => 'DPM Architect (AReport Pro)',
    ];

    private const LEGACY_PRODUCTS = ['validation', 'architect'];

    /** @var array<string, array> checks of this process, by key and host */
    private static array $checked = [];

    /** @param array{licensee: string, products: array<int,string>, domains: array<int,string>, expires: ?string} $payload */
    public static function issue(array $payload, string $secretKey): string
    {
        $body = self::b64(json_encode($payload + ['issued' => date('Y-m-d')], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $sig = sodium_crypto_sign_detached(self::PREFIX.'.'.$body, $secretKey);

        return self::PREFIX.'.'.$body.'.'.self::b64($sig);
    }

    /**
     * @return array{valid: bool, reason: ?string, licensee: ?string, products: array<int,string>, domains: array<int,string>, expires: ?string}
     */
    public static function check(?string $key, ?string $host = null, ?string $publicKey = null): array
    {
        $out = ['valid' => false, 'reason' => null, 'licensee' => null, 'products' => [], 'domains' => [], 'expires' => null];
        $key = trim((string) $key);
        if ($key === '') {
            return ['reason' => 'No license key.'] + $out;
        }
        $parts = explode('.', $key);
        if (count($parts) !== 3 || $parts[0] !== self::PREFIX) {
            return ['reason' => 'Not an AReport license key.'] + $out;
        }
        $sig = self::unb64($parts[2]);
        $pk = base64_decode($publicKey ?? self::PUBLIC_KEY, true);
        if ($sig === false || strlen($sig) !== SODIUM_CRYPTO_SIGN_BYTES || ! $pk
            || ! sodium_crypto_sign_verify_detached($sig, self::PREFIX.'.'.$parts[1], $pk)) {
            return ['reason' => 'The license signature is invalid.'] + $out;
        }
        $data = json_decode((string) self::unb64($parts[1]), true);
        if (! is_array($data)) {
            return ['reason' => 'The license content is unreadable.'] + $out;
        }
        $out['licensee'] = (string) ($data['licensee'] ?? '');
        $out['products'] = array_key_exists('products', $data) ? array_values(array_map('strtolower', (array) $data['products'])) : self::LEGACY_PRODUCTS;
        $out['domains'] = array_values(array_map('strtolower', (array) ($data['domains'] ?? [])));
        $out['expires'] = $data['expires'] ?? null;

        if ($out['expires'] && strtotime($out['expires'].' 23:59:59') < time()) {
            return ['reason' => 'The license expired on '.$out['expires'].'.'] + $out;
        }
        if ($host !== null && ! self::hostAllowed(strtolower($host), $out['domains'])) {
            return ['reason' => "The license does not cover {$host}."] + $out;
        }

        return ['valid' => true] + $out;
    }

    /** The configured license (areport.license.key), checked against the host of this request. */
    public static function current(): array
    {
        $key = (string) config('areport.license.key');
        $host = app()->runningInConsole() ? null : request()->getHost();

        return self::$checked[$key."\n".$host] ??= self::check($key, $host);
    }

    public static function enforced(): bool
    {
        return (bool) config('areport.license.enforcement', false);
    }

    /** Why the product may not be used here, or null when it may (always, with enforcement off). */
    public static function refusal(string $product): ?string
    {
        if (! self::enforced()) {
            return null;
        }
        $license = self::current();
        if (! $license['valid']) {
            return 'AReport license: '.$license['reason'];
        }
        if (! in_array($product, $license['products'], true)) {
            return 'AReport license: it does not include '.(self::PRODUCTS[$product] ?? $product).'.';
        }

        return null;
    }

    public static function covers(string $product): bool
    {
        return self::refusal($product) === null;
    }

    /** "example.com" covers example.com; "*.example.com" its subdomains; "*" any host. */
    private static function hostAllowed(string $host, array $domains): bool
    {
        foreach ($domains as $d) {
            if ($d === '*' || $d === $host || (str_starts_with($d, '*.') && str_ends_with($host, substr($d, 1)))) {
                return true;
            }
        }

        return false;
    }

    private static function b64(string $s): string
    {
        return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
    }

    private static function unb64(string $s): string|false
    {
        return base64_decode(strtr($s, '-_', '+/'), true);
    }
}
