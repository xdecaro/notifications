<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$version = trim((string) file_get_contents($root . '/VERSION'));
$package = $root . '/dist/pkg_xdecaronotifications_' . $version . '.zip';
$feed = $root . '/updates/pkg_xdecaronotifications.xml';

if ($version === '' || !is_file($package) || !is_file($feed)) {
    fwrite(STDERR, "Notifications release inputs are incomplete.\n");
    exit(1);
}

$digest = hash_file('sha256', $package);
$text = (string) file_get_contents($feed);
$count = 0;
$text = preg_replace(
    '/<sha256>[0-9a-fA-F]{64}<\/sha256>/',
    '<sha256>' . $digest . '</sha256>',
    $text,
    -1,
    $count
);

if (!is_string($text) || $count < 1) {
    fwrite(STDERR, "Unable to update Notifications SHA-256 in update feed.\n");
    exit(1);
}

file_put_contents($feed, $text);
libxml_use_internal_errors(true);
$xml = simplexml_load_file($feed);

if ($xml === false) {
    fwrite(STDERR, "Updated Notifications feed is not valid XML.\n");
    exit(1);
}

foreach ($xml->update as $update) {
    if (trim((string) $update->sha256) !== $digest) {
        fwrite(STDERR, "Notifications feed checksum verification failed.\n");
        exit(1);
    }
}

echo $digest . PHP_EOL;
