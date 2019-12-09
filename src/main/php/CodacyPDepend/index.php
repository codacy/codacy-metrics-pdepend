<?php

namespace CodacyPDepend;

require_once __DIR__ . '/../../../../vendor/autoload.php';
require_once('CodacyConfiguration.php');
require_once('CodacyReportGenerator.php');
require_once('CodacyResult.php');

use CodacyResult;
use PDepend\Application;
use Ds\Map;
use LineComplexity;

function pushToMapValueArray($map, $key, $value)
{
    if ($map->hasKey($key)) {
        array_push($map[$key], $value);
    } else {
        $map[$key] = array($value);
    }
}

function concatMapValueArray($map, $key, $array)
{
    if ($map->hasKey($key)) {
        $map[$key] = array_merge($map[$key], $array);
    } else {
        $map[$key] = $array;
    }
}

function mergeMapsOfArrays($res, $other)
{
    foreach ($other as $key => $value) {
        concatMapValueArray($res, $key, $value);
    }
}

function getFilename($content)
{
    $comp_unit = $content->getCompilationUnit();
    return $comp_unit->getFileName();
}

function filenameToFunctions($node)
{
    $map = new Map();
    foreach ($node->getFunctions() as $function) {
        $key = getFilename($function);
        pushToMapValueArray($map, $key, $function);
    }

    return $map;
}

function filenameToMethods($node)
{
    $map = new Map();
    foreach ($node->getClasses() as $content) {
        $key = getFilename($content);
        concatMapValueArray($map, $key, $content->getMethods());
    }
    return $map;
}

function contentMapToFileComplexities($contentMap, $cyclomaticAnalyzer)
{
    $res = new Map();
    foreach ($contentMap as $file => $nodes) {
        $res[$file] = array();
        foreach ($nodes as $node) {
            $ccn = $cyclomaticAnalyzer->getCcn($node);
            $line = $node->getStartLine();
            array_push($res[$file], new LineComplexity($line, $ccn));
        }
    }
    return $res;
}

function filesToNrClasses($result)
{
    $res = new Map();
    foreach ($result as $node) {
        foreach ($node->getClasses() as $class) {
            $file = getFilename($class);
            if ($res->hasKey($file)) $res[$file]++;
            else $res[$file] = 1;
        }
    }
    return $res;
}

function filesToNrMethods($result)
{
    $res = new Map();
    foreach ($result as $node) {
        foreach ($node->getClasses() as $class) {
            $file = getFilename($class);
            if ($res->hasKey($file)) $res[$file] += $class->getMethods()->count();
            else $res[$file] = $class->getMethods()->count();
        }
        foreach ($node->getFunctions() as $function) {
            $file = getFilename($function);
            if ($res->hasKey($file)) $res[$file] += 1;
            else $res[$file] = 1;
        }
    }
    return $res;
}

function resultToContentMap($result)
{
    $contentMap = new Map();
    foreach ($result as $node) {
        $files_to_methods = filenameToMethods($node);
        $files_to_functions = filenameToFunctions($node);
        foreach (array($files_to_methods, $files_to_functions) as $map) {
            mergeMapsOfArrays($contentMap, $map);
        }
    }
    return $contentMap;
}

function stripStringPrefix($str, $prefix)
{
    return substr($str, 0, strlen($prefix)) == $prefix ? substr($str, strlen($prefix)) : $str;
}

try {
    error_reporting(E_ERROR);
    $app = new Application();
    $engine = $app->getEngine();
    $codacyReportGenerator = new CodacyReportGenerator();
    $engine->addReportGenerator($codacyReportGenerator);
    addFilesFromConfiguration($engine);
    $result = $engine->analyze();
    $cyclomaticAnalyzer = $codacyReportGenerator->getCyclomaticComplexityAnalyzer();
    $contentMap = resultToContentMap($result);
    $fileToLineComplexities = contentMapToFileComplexities($contentMap, $cyclomaticAnalyzer);

    $filesToNrClasses = filesToNrClasses($result);
    $filesToNrMethods = filesToNrMethods($result);

    $files = $filesToNrClasses->keys()->union($filesToNrMethods->keys());

    foreach ($files as $file) {
        $lineComplexities = $fileToLineComplexities->hasKey($file) ?
            $fileToLineComplexities[$file] : array();

        $complexity = empty($lineComplexities) ? 0 : max(array_map(
            fn ($lineComplexity) => $lineComplexity->getValue(),
            $lineComplexities
        ));
        $nrClasses = $filesToNrClasses->hasKey($file) ?
            $filesToNrClasses[$file] : 0;
        $nrMethods = $filesToNrMethods->hasKey($file) ?
            $filesToNrMethods[$file] : 0;

        $fileRelativeToSrc = stripStringPrefix($file, "/src/");

        $codacyResult = new CodacyResult($fileRelativeToSrc, $complexity, $nrMethods, $nrClasses, $fileToLineComplexities[$file]);
        print(json_encode($codacyResult, JSON_UNESCAPED_SLASHES) . PHP_EOL);
    }
} catch (\Exception $e) {
    print($e->getMessage() . PHP_EOL);
    print($e->getTraceAsString() . PHP_EOL);
    exit(1);
}
