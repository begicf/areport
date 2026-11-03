<?php

namespace App\Support;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

/**
 * XBRL taxonomy packages (https://www.xbrl.org/Specification/taxonomy-package/REC-2016-04-19/):
 * a zip whose package folder holds META-INF/taxonomyPackage.xml next to one folder
 * per host (www.eba.europa.eu, www.fba.ba…). Finds the package folders in an
 * extracted zip, reads their metadata, and merges their host folders into a
 * taxonomy folder, so an extension package (a national regulator's) can sit
 * next to the EBA package it builds on.
 */
class TaxonomyPackage
{
    /**
     * What a taxonomy package holds (schemas, linkbases, xBRL-CSV metadata, readme).
     * Anything else (.php, .html, .js, .svg…) is left in the zip: taxonomy folders are
     * served under /storage, so a script or page in them would run on this site.
     */
    public const EXTENSIONS = ['xml', 'xsd', 'json', 'csv', 'txt'];

    /** Unpacked size above which an upload is refused (a full EBA release is about 8 GB). */
    public const MAX_UNPACKED_BYTES = 20 * 1024 ** 3;

    /** Extract a zip into $dir (created): only taxonomy files, refusing entries that would escape it. */
    public static function extract(string $zipFile, string $dir): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipFile) !== true) {
            throw new \RuntimeException('The file is not a valid zip archive.');
        }
        $keep = [];
        $bytes = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = str_replace('\\', '/', (string) $stat['name']);
            if (str_starts_with($name, '/') || preg_match('#(^|/)\.\.(/|$)|^[A-Za-z]:#', $name)) {
                $zip->close();
                throw new \RuntimeException('The archive contains an unsafe path: '.$name);
            }
            if (str_ends_with($name, '/') || ! in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), self::EXTENSIONS, true)) {
                continue;
            }
            $bytes += (int) $stat['size'];
            if ($bytes > self::MAX_UNPACKED_BYTES) {
                $zip->close();
                throw new \RuntimeException('The archive unpacks to more than 20 GB.');
            }
            $keep[] = (string) $stat['name'];
        }
        @mkdir($dir, 0775, true);
        if ($keep) {
            $zip->extractTo($dir, $keep);
        }
        $zip->close();
    }

    /**
     * Package folders in an extracted zip: folders holding META-INF/taxonomyPackage.xml
     * (the extraction folder itself when the zip has no top folder).
     *
     * @return array<int,string>
     */
    public static function roots(string $dir): array
    {
        $roots = [];
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
        $it->setMaxDepth(3);
        foreach ($it as $file) {
            if ($file->isFile() && $file->getFilename() === 'taxonomyPackage.xml' && basename($file->getPath()) === 'META-INF') {
                $roots[] = dirname($file->getPath());
            }
        }
        sort($roots);

        return $roots;
    }

    /** @return array{identifier: ?string, name: ?string, version: ?string, publisher: ?string, entry_points: array<int,array{name: string, href: string}>} */
    public static function info(string $root): array
    {
        $info = ['identifier' => null, 'name' => null, 'version' => null, 'publisher' => null, 'entry_points' => []];
        $file = $root.'/META-INF/taxonomyPackage.xml';
        if (! is_file($file)) {
            return $info;
        }
        $dom = new \DOMDocument();
        if (! @$dom->load($file)) {
            return $info;
        }
        $xp = new \DOMXPath($dom);
        $xp->registerNamespace('tp', $dom->documentElement->namespaceURI ?: 'http://xbrl.org/2016/taxonomy-package');
        $text = fn (string $q) => ($n = $xp->query($q)->item(0)) ? trim($n->textContent) : null;
        $info['identifier'] = $text('/tp:taxonomyPackage/tp:identifier');
        $info['name'] = $text('/tp:taxonomyPackage/tp:name');
        $info['version'] = $text('/tp:taxonomyPackage/tp:version');
        $info['publisher'] = $text('/tp:taxonomyPackage/tp:publisher');
        foreach ($xp->query('/tp:taxonomyPackage/tp:entryPoints/tp:entryPoint') as $ep) {
            $name = ($n = $xp->query('tp:name', $ep)->item(0)) ? trim($n->textContent) : '';
            foreach ($xp->query('tp:entryPointDocument', $ep) as $doc) {
                $info['entry_points'][] = ['name' => $name, 'href' => (string) $doc->getAttribute('href')];
            }
        }

        return $info;
    }

    /** Copy a package's host folders (everything but META-INF) into $target; returns the number of files. */
    public static function merge(string $root, string $target): int
    {
        $count = 0;
        foreach (glob($root.'/*', GLOB_ONLYDIR) ?: [] as $dir) {
            if (basename($dir) === 'META-INF') {
                continue;
            }
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
            foreach ($it as $file) {
                $dest = $target.'/'.substr($file->getPathname(), strlen($root) + 1);
                if ($file->isDir()) {
                    @mkdir($dest, 0775, true);
                } else {
                    @mkdir(dirname($dest), 0775, true);
                    copy($file->getPathname(), $dest);
                    $count++;
                }
            }
        }

        return $count;
    }

    public static function removeDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $file) {
            $file->isDir() && ! $file->isLink() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($dir);
    }
}
