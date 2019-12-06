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

function genericMap($f, $list)
{
    $res = array();
    foreach ($list as $e) {
        array_push($res, $f($e));
    }
    return $res;
}

function pushToMapValueArray($map, $key, $value)
{
    if ($map->hasKey($key)) {
        $map[$key] = array_push($map[$key], $value);
    } else {
        $map[$key] = [$value];
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

function mergeMapsOfArrays($res, $other, $f)
{
    foreach ($other as $key => $value) {
        concatMapValueArray($res, $key, genericMap($f, $value));
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
    foreach ($node->getFunctions() as $content) {
        $key = getFilename($content);
        pushToMapValueArray($map, $key, $content);
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

function contentMapToFileComplexities($content_map, $cyclomaticAnalyzer)
{
    $res = new Map();
    foreach ($content_map as $file => $nodes) {
        $res[$file] = genericMap(
            function ($node) use ($cyclomaticAnalyzer) {
                $ccn = $cyclomaticAnalyzer->getCcn($node);
                $line = $node->getStartLine();
                return new LineComplexity($line, $ccn);
            },
            $nodes
        );
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
    $content_map = new Map();
    foreach ($result as $node) {
        $files_to_methods = filenameToMethods($node);
        $files_to_functions = filenameToFunctions($node);
        foreach (array($files_to_methods, $files_to_functions) as $map) {
            mergeMapsOfArrays($content_map, $map, fn ($e) => $e);
        }
    }
    return $content_map;
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
    $cyclomaticAnalyzer = $codacyReportGenerator->getCyclomaticComplexityAnalyzer();
    $content_map = resultToContentMap($result);
    $fileToLineComplexities = contentMapToFileComplexities($content_map, $cyclomaticAnalyzer);

    $filesToNrClasses = filesToNrClasses($result);
    $filesToNrMethods = filesToNrMethods($result);

    $files = $filesToNrClasses->keys()->union($filesToNrMethods->keys());

    foreach ($files as $file) {
        $lineComplexities = $fileToLineComplexities->hasKey($file) ?
            $fileToLineComplexities[$file] : array();

        $complexity = empty($lineComplexities) ? 0 : max(genericMap(
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
} catch (Exception $e) {
    echo ($e->getMessage() . PHP_EOL);
    echo ($e->getTraceAsString() . PHP_EOL);
    exit(1);
}
