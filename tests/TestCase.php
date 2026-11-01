<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /** AReport Pro runs only next to AReport DPM; skip a test that needs it otherwise. */
    protected function requirePro(): void
    {
        if (! class_exists(\Begicf\AReportDpm\AReportDpmServiceProvider::class) || ! class_exists(\Begicf\AReportPro\AReportProServiceProvider::class)) {
            $this->markTestSkipped('areport-pro (with areport-dpm) is not installed.');
        }
    }

    /** Skip a test that needs AReport DPM (the DPM model and DPM-XL validation). */
    protected function requireDpm(): void
    {
        if (! class_exists(\Begicf\AReportDpm\AReportDpmServiceProvider::class)) {
            $this->markTestSkipped('areport-dpm is not installed.');
        }
    }
}
