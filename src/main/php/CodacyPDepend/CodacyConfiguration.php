<?php

namespace CodacyPDepend;

use PDepend\Engine;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

function string_ends_with($str, $test)
{
    return substr_compare($str, $test, -strlen($test)) === 0;
}

function addDirectoryRecursively(Engine $engine, $dir)
{
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($rii as $file) {
        if ($file->isDir()) {
            continue;
        }
        $filename = $file->getPathname();
        if (string_ends_with($filename, ".php"))
            $engine->addFile($file->getPathname());
    }
}

function walkDirectoryRecursively($dir)
{
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($rii as $file) {
        if ($file->isDir()) {
            continue;
        }
        $filename = $file->getPathname();

        if (!$endsWith($filename, ".php"))
            $engine->addFile($file->getPathname());
    }
}

function addFilesFromConfiguration(Engine $engine)
{
    $srcdir = '/src';
    $codacyrc_name = "/.codacyrc";
    if (file_exists($codacyrc_name)) {
        $codacyrc_file = file_get_contents($codacyrc_name);
        $codacyrc = json_decode($codacyrc_file);
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
