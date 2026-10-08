<?php

/**
 * Lists every translation key used in app/ and resources/views and reports
 * the ones missing from lang/{locale}.json.
 *
 * Usage: php scripts/extract-translations.php [locale ...]
 */
$root = dirname(__DIR__);
$keys = [];

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/app'));
$files = iterator_to_array($iterator);
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/resources/views'));
$files = array_merge($files, iterator_to_array($iterator));

$pattern = '/(?:__|trans_choice|@lang)\(\s*(\'(?:[^\'\\\\]|\\\\.)*\'|"(?:[^"\\\\]|\\\\.)*")/s';

foreach ($files as $file) {
    if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.php')) {
        continue;
    }

    preg_match_all($pattern, file_get_contents($file->getPathname()), $matches);

    foreach ($matches[1] as $literal) {
        $quote = $literal[0];
        $value = substr($literal, 1, -1);
        $value = $quote === "'" ? str_replace(["\\'", '\\\\'], ["'", '\\'], $value) : stripcslashes($value);
        $keys[$value] = true;
    }
}

ksort($keys);
$keys = array_keys($keys);

$locales = array_slice($argv, 1);

if ($locales === []) {
    echo json_encode($keys, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
    exit(0);
}

$status = 0;

foreach ($locales as $locale) {
    $path = "{$root}/lang/{$locale}.json";
    $translations = file_exists($path) ? json_decode(file_get_contents($path), true) : [];
    // Keys such as "auth.password" live in lang/{locale}/*.php files.
    $missing = array_values(array_filter($keys, fn ($key) => ! array_key_exists($key, $translations) && ! preg_match('/^[a-z_]+\.[a-z_.]+$/', $key)));

    echo "{$locale}: ".count($keys).' keys, '.count($missing)." missing\n";

    foreach ($missing as $key) {
        echo "  - {$key}\n";
    }

    $status = $missing ? 1 : $status;
}

exit($status);
