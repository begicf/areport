<?php

namespace App\Support;

use App\Model\Taxonomy;
use Symfony\Component\Process\Process;

/**
 * Validates a generated entry point (module schema) with Arelle, offline: Arelle's
 * HTTP cache is pointed at the taxonomy folder (one symlink per host directory —
 * www.eba.europa.eu, www.xbrl.org, the owner's host…), so every official URL in
 * the DTS resolves to the local copy. Returns Arelle's messages grouped by level.
 */
class ArelleValidator
{
    public function __construct(private Taxonomy $taxonomy)
    {
    }

    public static function available(): bool
    {
        $cmd = config('areport.arelle.cmd');

        return $cmd && is_file($cmd) && is_executable($cmd);
    }

    /**
     * @return array{ok: bool, errors: int, warnings: int, seconds: float, messages: array<int, array{level: string, code: string, text: string, ref: ?string}>}
     */
    public function validate(string $entryUrl): array
    {
        if (! self::available()) {
            throw new \RuntimeException('Arelle is not configured — set AREPORT_ARELLE_CMD to the arelleCmdLine executable.');
        }
        $home = $this->home();
        $log = tempnam(sys_get_temp_dir(), 'arelle').'.json';

        $process = new Process([
            config('areport.arelle.cmd'), '--file', $entryUrl, '--validate',
            // Without reference properties: a large instance's formula messages would
            // otherwise make the log hundreds of MB.
            '--internetConnectivity=offline', '--logNoRefObjectProperties', '--logFile', $log,
        ], null, ['XDG_CONFIG_HOME' => $home], null, (float) config('areport.arelle.timeout', 300));
        $started = microtime(true);
        $process->run();
        $seconds = round(microtime(true) - $started, 1);

        $json = is_file($log) ? json_decode((string) file_get_contents($log), true) : null;
        @unlink($log);
        if (! is_array($json)) {
            throw new \RuntimeException('Arelle did not produce a log: '.trim(mb_substr($process->getErrorOutput() ?: $process->getOutput(), 0, 500)));
        }

        $messages = [];
        foreach ($json['log'] ?? [] as $m) {
            $level = strtolower((string) ($m['level'] ?? 'info'));
            if ($level === 'debug') {
                continue;
            }
            $text = (string) ($m['message']['text'] ?? '');
            $code = (string) ($m['code'] ?? '');
            // Arelle prefixes the text with "[code] "; drop it, the code has its own column.
            $text = preg_replace('/^\['.preg_quote($code, '/').'\]\s*/', '', $text);
            $messages[] = [
                'level' => $level,
                'code' => $code,
                'text' => trim($text),
                'ref' => $m['refs'][0]['href'] ?? null,
            ];
        }
        $count = fn (array $levels) => count(array_filter($messages, fn ($m) => in_array($m['level'], $levels, true)));
        $errors = $count(['error', 'critical', 'fatal']);

        return [
            'ok' => $errors === 0,
            'errors' => $errors,
            'warnings' => $count(['warning', 'inconsistency']),
            'seconds' => $seconds,
            'messages' => $messages,
        ];
    }

    /** Arelle config home whose HTTP cache maps each host to the taxonomy folder. */
    private function home(): string
    {
        $root = storage_path('app/public/tax/'.$this->taxonomy->folder);
        $home = storage_path('app/arelle-home/'.$this->taxonomy->folder);
        $cache = $home.'/arelle/cache/http';
        if (! is_dir($cache)) {
            mkdir($cache, 0775, true);
        }
        foreach (glob($root.'/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $host = basename($dir);
            if (! str_contains($host, '.')) {
                continue;   // only host folders (www.eba.europa.eu, www.fba.ba…)
            }
            $link = $cache.'/'.$host;
            if (is_link($link) && readlink($link) !== $dir) {
                unlink($link);
            }
            if (! file_exists($link)) {
                symlink($dir, $link);
            }
        }

        return $home;
    }
}
