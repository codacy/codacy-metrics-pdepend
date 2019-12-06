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

function generic_map($f, $list)
{
    $res = array();
    foreach ($list as $e) {
        array_push($res, $f($e));
    }
    return $res;
}

function push_to_map_value_array($map, $key, $value)
{
    if ($map->hasKey($key)) {
        $map[$key] = array_push($map[$key], $value);
    } else {
        $map[$key] = [$value];
    }
}

function concat_map_value_array($map, $key, $array)
{
    if ($map->hasKey($key)) {
        $map[$key] = array_merge($map[$key], $array);
    } else {
        $map[$key] = $array;
    }
}

function merge_maps_of_arrays($res, $other, $f)
{
    foreach ($other as $key => $value) {
        concat_map_value_array($res, $key, generic_map($f, $value));
    }
}

function get_filename($content)
{
    $comp_unit = $content->getCompilationUnit();
    return $comp_unit->getFileName();
}

function filename_to_functions($node)
{
    $map = new Map();
    foreach ($node->getFunctions() as $content) {
        $key = get_filename($content);
        push_to_map_value_array($map, $key, $content);
    }
    return $map;
}

function filename_to_methods($node)
{
    $map = new Map();
    foreach ($node->getClasses() as $content) {
        $key = get_filename($content);
        concat_map_value_array($map, $key, $content->getMethods());
    }
    return $map;
}

function content_map_to_fileComplexities($content_map, $cyclomatic_analyzer)
{
    $res = new Map();
    foreach ($content_map as $file => $nodes) {
        $res[$file] = generic_map(
            function ($node) use ($cyclomatic_analyzer) {
                $ccn = $cyclomatic_analyzer->getCcn($node);
                $line = $node->getStartLine();
                return new LineComplexity($line, $ccn);
            },
            $nodes
        );
    }
    return $res;
}

function files_to_nrClasses($result)
{
    $res = new Map();
    foreach ($result as $node) {
        foreach ($node->getClasses() as $class) {
            $file = get_filename($class);
            if ($res->hasKey($file)) $res[$file]++;
            else $res[$file] = 1;
        }
    }
    return $res;
}

function files_to_nrMethods($result)
{
    $res = new Map();
    foreach ($result as $node) {
        foreach ($node->getClasses() as $class) {
            $file = get_filename($class);
            if ($res->hasKey($file)) $res[$file] += $class->getMethods()->count();
            else $res[$file] = $class->getMethods()->count();
        }
        foreach ($node->getFunctions() as $function) {
            $file = get_filename($function);
            if ($res->hasKey($file)) $res[$file] += 1;
            else $res[$file] = 1;
        }
    }
    return $res;
}

function result_to_content_map($result)
{
    $content_map = new Map();
    foreach ($result as $node) {
        $files_to_methods = filename_to_methods($node);
        $files_to_functions = filename_to_functions($node);
        foreach (array($files_to_methods, $files_to_functions) as $map) {
            merge_maps_of_arrays($content_map, $map, fn ($e) => $e);
        }
    }
    return $content_map;
}

function strip_string_prefix($str, $prefix)
{
    return substr($str, 0, strlen($prefix)) == $prefix ? substr($str, strlen($prefix)) : $str;
}

try {
    error_reporting(E_ERROR);
    $app = new Application();
    $engine = $app->getEngine();
    $codacy_logger = new CodacyReportGenerator();
    $engine->addReportGenerator($codacy_logger);
    addFilesFromConfiguration($engine);
    $result = $engine->analyze();
    $cyclomatic_analyzer = $codacy_logger->getCyclomaticAnalyzer();
    $content_map = result_to_content_map($result);
    $file_to_lineComplexities = content_map_to_fileComplexities($content_map, $cyclomatic_analyzer);

    $files_to_nrClasses = files_to_nrClasses($result);
    $files_to_nrMethods = files_to_nrMethods($result);

    $files = $files_to_nrClasses->keys()->union($files_to_nrMethods->keys());

    foreach ($files as $file) {
        $lineComplexities = $file_to_lineComplexities->hasKey($file) ?
            $file_to_lineComplexities[$file] : array();

        $complexity = empty($lineComplexities) ? 0 : max(generic_map(
            fn ($lineComplexity) => $lineComplexity->get_value(),
            $lineComplexities
        ));
        $nrClasses = $files_to_nrClasses->hasKey($file) ?
            $files_to_nrClasses[$file] : 0;
        $nrMethods = $files_to_nrMethods->hasKey($file) ?
            $files_to_nrMethods[$file] : 0;

        $file_relative_to_src = strip_string_prefix($file, "/src/");

        $codacy_result = new CodacyResult($file_relative_to_src, $complexity, $nrMethods, $nrClasses, $file_to_lineComplexities[$file]);
        print(json_encode($codacy_result, JSON_UNESCAPED_SLASHES) . PHP_EOL);
    }
} catch (Exception $e) {
    echo ($e->getMessage() . PHP_EOL);
    echo ($e->getTraceAsString() . PHP_EOL);
    exit(1);
}
