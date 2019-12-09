<?php

namespace Codacy\PDepend;

require_once __DIR__ . '/../vendor/autoload.php';
require_once 'CodacyConfiguration.php';
require_once 'CodacyReportGenerator.php';
require_once 'CodacyResult.php';

use PDepend\Application;

function arraysOfTuplesToMap(&...$arraysOfTuples)
{
    $res = array();
    foreach ($arraysOfTuples as $arrayOfTuples) {
        foreach ($arrayOfTuples as [$key, $value]) {
            $res[$key] = $res[$key] === null ? $value : array_merge($res[$key], $value);
        }
    }
    return $res;
}

function getFilename($content)
{
    $comp_unit = $content->getCompilationUnit();
    return $comp_unit->getFileName();
}

function filenameToFunctions($node)
{
    foreach ($node->getFunctions() as $function) {
        yield array(getFilename($function), array($function));
    }
}

function filenameToMethods($node)
{
    foreach ($node->getClasses() as $class) {
        yield array(getFilename($class), $class->getMethods());
    }
}

function contentToFileComplexities($content, $cyclomaticAnalyzer)
{
    $res = array();
    foreach ($content as $file => $nodes) {
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
    $res = array();
    foreach ($result as $node) {
        foreach ($node->getClasses() as $class) {
            $file = getFilename($class);
            $res[$file] = ($res[$file] ?: 0) + 1;
        }
    }
    return $res;
}

function filesToNrMethods($result)
{
    $res = array();
    foreach ($result as $node) {
        foreach ($node->getClasses() as $class) {
            $file = getFilename($class);
            $res[$file] = ($res[$file] ?: 0) + $class->getMethods()->count();
        }
        foreach ($node->getFunctions() as $function) {
            $file = getFilename($function);
            $res[$file] = ($res[$file] ?: 0) + 1;
        }
    }
    return $res;
}
/**
 * Creates 
 * @param PDepend\Source\AST\ASTNamespace $result 
 */
function resultToContent($result)
{
    $resultArrays = array();
    foreach ($result as $node) {
        array_push($resultArrays, filenameToMethods($node));
        array_push($resultArrays, filenameToFunctions($node));
    }
    return arraysOfTuplesToMap(...$resultArrays);
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
    $content = resultToContent($result);
    $fileToLineComplexities = contentToFileComplexities($content, $cyclomaticAnalyzer);

    $filesToNrClasses = filesToNrClasses($result);
    $filesToNrMethods = filesToNrMethods($result);

    $files = array_unique(array_merge(array_keys($filesToNrClasses), (array_keys($filesToNrMethods))));

    foreach ($files as $file) {
        $lineComplexities = $fileToLineComplexities[$file] ?: array();

        $complexity = empty($lineComplexities) ? 0 : max(array_map(
            fn ($lineComplexity) => $lineComplexity->getValue(),
            $lineComplexities
        ));
        $nrClasses = $filesToNrClasses[$file] ?: 0;
        $nrMethods = $filesToNrMethods[$file] ?: 0;

        $fileRelativeToSrc = stripStringPrefix($file, "/src/");

        $codacyResult = new CodacyResult($fileRelativeToSrc, $complexity, $nrMethods, $nrClasses, $fileToLineComplexities[$file]);
        print(json_encode($codacyResult, JSON_UNESCAPED_SLASHES) . PHP_EOL);
    }
} catch (\Exception $e) {
    print($e->getMessage() . PHP_EOL);
    print($e->getTraceAsString() . PHP_EOL);
    exit(1);
}
