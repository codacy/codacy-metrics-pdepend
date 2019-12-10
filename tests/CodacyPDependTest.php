<?php

namespace Codacy\PDepend;

require_once __DIR__ . '/../src/CodacyPDepend.php';

use PDepend\Source\AST\ASTCompilationUnit;
use PDepend\Source\AST\ASTFunction;
use PDepend\Source\AST\ASTNamespace;
use PHPUnit\Framework\TestCase;

final class CodacyPDependTest extends TestCase
{
    function testArraysOfTuplesToMap()
    {
        $input1 = array(
            array("abc", array(1, 2)),
            array("b", array(1)),
            array("b", array(2))
        );

        $input2 = array(
            array("a", array(1, 2)),
            array("abc", array(3)),
            array("b", array(7))
        );

        $expectedResult = array(
            "a" => array(1, 2),
            "abc" => array(1, 2, 3),
            "b" => array(1, 2, 7)
        );
        $result = arraysOfTuplesToMap($input1, $input2);
        $this->assertEquals($result, $expectedResult);
    }

    function testArraysOfTuplesToMapDuplicatedValues()
    {
        $input = array(
            array("a", array(1, 2)),
            array("a", array(1, 2))
        );

        $expectedResult = array(
            "a" => array(1, 2, 1, 2)
        );
        $result = arraysOfTuplesToMap($input);
        $this->assertEquals($result, $expectedResult);
    }

    function testFilenameToFunctions()
    {
        $createFunction = function ($name, $filename) {
            $compilationUnit = $this->createMock(ASTCompilationUnit::class);
            $compilationUnit->method('getFileName')->willReturn($filename);
            $f = $this->createMock(ASTFunction::class);
            $f->method('getCompilationUnit')->willReturn($compilationUnit);
            $f->method('getName')->willReturn($name);
            return $f;
        };

        $file1 = 'file1.php';
        $file2 = 'file2.php';
        $f1 = 'f1';
        $f2 = 'f2';
        $f3 = 'f3';
        $function1 = $createFunction($f1, $file1);
        $function2 = $createFunction($f2, $file1);
        $function3 = $createFunction($f3, $file2);
        $astNamespaceMock = $this->createMock(ASTNamespace::class);
        $astNamespaceMock->method('getFunctions')->willReturn(array($function1, $function2, $function3));

        $expectedResult = array(
            array($file1, array($f1)),
            array($file1, array($f2)),
            array($file2, array($f3))
        );

        $result = iterator_to_array(filenameToFunctions($astNamespaceMock));
        $resultWithFunctionNames = array_map(
            fn ($t) => array($t[0], array_map(
                fn ($e) => $e->getName(),
                $t[1]
            )),
            $result
        );
        $this->assertEquals(count($result), 3);
        $this->assertEquals($expectedResult, $resultWithFunctionNames);
    }
}
