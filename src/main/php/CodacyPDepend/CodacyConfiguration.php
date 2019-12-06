<?php

namespace CodacyPDepend;

use PDepend\Engine;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

function stringEndsWith($str, $test)
{
    return substr_compare($str, $test, -strlen($test)) === 0;
}

function addDirectoryRecursively(Engine $engine, string $dir)
{
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($rii as $file) {
        if ($file->isDir()) {
            continue;
        }
        $filename = $file->getPathname();
        if (stringEndsWith($filename, ".php"))
            $engine->addFile($file->getPathname());
    }
}

function addFilesFromConfiguration(Engine $engine)
{
    $srcdir = '/src';
    $codacyrcName = "/.codacyrc";
    if (file_exists($codacyrcName)) {
        $codacyrcFile = file_get_contents($codacyrcName);
        $codacyrc = json_decode($codacyrcFile);
        if (json_last_error() === JSON_ERROR_NONE && property_exists($codacyrc, 'files')) {
            $files = $codacyrc->{'files'};
            foreach ($files as $file) {
                $engine->addFile(join('/', array($srcdir, $file)));
            }
            return;
        }
    }
    addDirectoryRecursively($engine, $srcdir);
}
