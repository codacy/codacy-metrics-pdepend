<?php

namespace Codacy\PDepend;

use PDepend\Metrics\Analyzer\CyclomaticComplexityAnalyzer;
use PDepend\Metrics\Analyzer\NodeLocAnalyzer;

require_once __DIR__ . '/../vendor/autoload.php';
require_once 'CodacyConfiguration.php';
require_once 'CodacyReportGenerator.php';
require_once 'CodacyResult.php';

function tuplesToMapWithArrayValues($arrayOfTuples)
{
    $res = [];
    foreach ($arrayOfTuples as [$key, $value]) {
        if (array_key_exists($key, $res))
            array_push($res[$key], $value);
        else
            $res[$key] = [$value];
    }
    return $res;
}

function getFilename($content)
{
    $comp_unit = $content->getCompilationUnit();
    return $comp_unit->getFileName();
}
/**
 * @param \PDepend\Source\AST\ASTNamespace $namespace
 */
function filenameToFunctions($namespace)
{
    foreach ($namespace->getFunctions() as $function) {
        yield [getFilename($function), $function];
    }
}
/**
 * @param \PDepend\Source\AST\ASTNamespace $namespace
 */
function filenameToMethods($namespace)
{
    foreach ($namespace->getClasses() as $class) {
        foreach ($class->getMethods() as $method) {
            yield [getFilename($class), $method];
        }
    }
}

function contentToFileComplexities($content, CyclomaticComplexityAnalyzer $cyclomaticAnalyzer)
{
    $res = [];
    foreach ($content as $file => $nodes) {
        $res[$file] = [];
        foreach ($nodes as $node) {
            $ccn = $cyclomaticAnalyzer->getCcn($node);
            $line = $node->getStartLine();
            array_push($res[$file], new LineComplexity($line, $ccn));
        }
    }
    return $res;
}

function valueOrZero($key, $array)
{
    return array_key_exists($key, $array) ? $array[$key] : 0;
}

function filesToNrClasses($result)
{
    $res = [];
    foreach ($result as $node) {
        foreach ($node->getClasses() as $class) {
            $file = getFilename($class);
            $res[$file] = valueOrZero($file, $res) + 1;
        }
    }
    return $res;
}

function filesToNrMethods($result)
{
    $res = [];
    foreach ($result as $node) {
        foreach ($node->getClasses() as $class) {
            $file = getFilename($class);
            $res[$file] = valueOrZero($file, $res) + $class->getMethods()->count();
        }
        foreach ($node->getFunctions() as $function) {
            $file = getFilename($function);
            $res[$file] = valueOrZero($file, $res) + 1;
        }
    }
    return $res;
}

function filesToNodeMetrics($result, NodeLocAnalyzer $nodeLocAnalyzer)
{
    $generator = function () use ($result, $nodeLocAnalyzer) {
        foreach ($result as $node) {
            foreach ($node->getClasses() as $class) {
                $file = $class->getCompilationUnit();
                yield $file->getFileName() => $nodeLocAnalyzer->getNodeMetrics($file);
            }
            foreach ($node->getFunctions() as $function) {
                $file = $function->getCompilationUnit();
                yield $file->getFileName() => $nodeLocAnalyzer->getNodeMetrics($file);
            }
        }
    };
    return iterator_to_array($generator());
}
/**
 * @param \PDepend\Source\AST\ASTNamespace[] $result
 */
function resultToContent($result)
{
    $generator = function () use ($result) {
        foreach ($result as $node) {
            yield from filenameToMethods($node);
            yield from filenameToFunctions($node);
        }
    };
    return tuplesToMapWithArrayValues($generator());
}

function stripStringPrefix($str, $prefix)
{
    return substr($str, 0, strlen($prefix)) == $prefix ? substr($str, strlen($prefix)) : $str;
}
