<?php

namespace App\Console\Commands;

use App\Support\License;
use Illuminate\Console\Command;

/** Show the configured license: whom it is for, its hosts and expiry, and the products it includes. */
class ShowLicense extends Command
{
    protected $signature = 'areport:license';

    protected $description = 'Show the configured AReport license key';

    public function handle(): int
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
