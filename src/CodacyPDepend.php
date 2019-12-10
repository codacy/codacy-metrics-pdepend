<?php

namespace Codacy\PDepend;

require_once __DIR__ . '/../vendor/autoload.php';
require_once 'CodacyConfiguration.php';
require_once 'CodacyReportGenerator.php';
require_once 'CodacyResult.php';

function arraysOfTuplesToMap(&...$arraysOfTuples)
{
    $res = array();
    foreach ($arraysOfTuples as $arrayOfTuples) {
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
