<?php

namespace Codacy\PDepend;

use PDepend\Engine;

function stringEndsWith($str, $test)
{
    return substr_compare($str, $test, -strlen($test)) === 0;
}

function filesFromConfiguration()
{
    $srcdir = '/src';
    $codacyrcName = "/.codacyrc";
    if (file_exists($codacyrcName)) {
        $codacyrcFile = file_get_contents($codacyrcName);
        $codacyrc = json_decode($codacyrcFile);
        if (json_last_error() === JSON_ERROR_NONE && property_exists($codacyrc, 'files')) {
            $files = $codacyrc->{'files'};
            foreach ($files as $file) {
                yield join('/', [$srcdir, $file]);
            }
            return;
        }
    }
    $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($srcdir));
    foreach ($rii as $file) {
        if ($file->isDir()) {
            continue;
        }
        $filename = $file->getPathname();
        if (stringEndsWith($filename, ".php")) {
            yield $file->getPathname();
        }
    }
}

function addFilesToEngine(Engine $engine, $files)
{
    foreach ($files as $file) {
        $engine->addFile($file);
    }
}
