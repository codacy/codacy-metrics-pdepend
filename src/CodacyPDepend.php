<?php

namespace Codacy\PDepend;

use PDepend\Metrics\Analyzer\CyclomaticComplexityAnalyzer;
use PDepend\Metrics\Analyzer\NodeLocAnalyzer;

require_once __DIR__ . '/../vendor/autoload.php';
require_once 'CodacyConfiguration.php';
require_once 'CodacyReportGenerator.php';
require_once 'CodacyResult.php';

function arrayOfArraysOfTuplesToMap($arrayOfArraysOfTuples)
{
    $res = [];
    foreach ($arrayOfArraysOfTuples as $arrayOfTuples) {
        foreach ($arrayOfTuples as [$key, $value]) {
            $res[$key] = array_key_exists($key, $res) ? array_merge($res[$key], $value) : $value;
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
        yield [getFilename($function), [$function]];
    }
}

function filenameToMethods($node)
{
    foreach ($node->getClasses() as $class) {
        yield [getFilename($class), $class->getMethods()];
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
            yield filenameToMethods($node);
            yield filenameToFunctions($node);
        }
    };
    return arrayOfArraysOfTuplesToMap($generator());
}

function stripStringPrefix($str, $prefix)
{
    return substr($str, 0, strlen($prefix)) == $prefix ? substr($str, strlen($prefix)) : $str;
}
