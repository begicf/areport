<?php

namespace App\Support;

/**
 * parse_str() without PHP's max_input_vars cap. A table form is posted as one
 * serialized string (two fields per cell, value and dim), so a sheet with more
 * than ~500 cells exceeded the default limit of 1000 and could not be saved or
 * validated. Handles the shapes the form uses: name, name[key], name[key][].
 */
class FormQuery
{
    public static function parse(string $query): array
    {
        $out = [];
        foreach ($query === '' ? [] : explode('&', $query) as $pair) {
            if ($pair === '') {
                continue;
            }
            [$rawKey, $rawValue] = array_pad(explode('=', $pair, 2), 2, '');
            $key = urldecode($rawKey);
            $value = urldecode($rawValue);

            if (! preg_match('/^([^\[]+)((?:\[[^\]]*\])*)$/', $key, $m)) {
                continue;
            }
            $path = [$m[1]];
            if ($m[2] !== '') {
                preg_match_all('/\[([^\]]*)\]/', $m[2], $parts);
                $path = array_merge($path, $parts[1]);
            }

            $ref = &$out;
            $last = count($path) - 1;
            foreach ($path as $i => $segment) {
                if ($i === $last) {
                    if ($segment === '') {
                        $ref[] = $value;
                    } else {
                        $ref[$segment] = $value;
                    }
                    break;
                }
                if ($segment === '') {
                    $ref[] = [];
                    end($ref);
                    $segment = key($ref);
                }
                if (! isset($ref[$segment]) || ! is_array($ref[$segment])) {
                    $ref[$segment] = [];
                }
                $ref = &$ref[$segment];
            }
            unset($ref);
        }

        return $out;
    }
}
