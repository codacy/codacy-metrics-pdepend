<?php

namespace Codacy\PDepend;

require_once __DIR__ . '/../src/CodacyPDepend.php';

use PDepend\Source\AST\ASTClass;
use PDepend\Source\AST\ASTCompilationUnit;
use PDepend\Source\AST\ASTFunction;
use PDepend\Source\AST\ASTMethod;
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
        $this->assertEquals($expectedResult, $resultWithFunctionNames);
    }

    function testFilenameToMethods()
    {
        $createMethod = function ($name) {
            $method = $this->createMock(ASTMethod::class);
            $method->method('getName')->willReturn($name);
            return $method;
        };

        $createClass = function ($methods, $filename) {
            $compilationUnit = $this->createMock(ASTCompilationUnit::class);
            $compilationUnit->method('getFileName')->willReturn($filename);
            $class = $this->createMock(ASTClass::class);
            $class->method('getCompilationUnit')->willReturn($compilationUnit);
            $class->method('getMethods')->willReturn($methods);
            return $class;
        };

        $file1 = 'file1.php';
        $file2 = 'file2.php';
        $m1 = 'm1';
        $m2 = 'm2';
        $m3 = 'm3';
        $method1 = $createMethod($m1);
        $method2 = $createMethod($m2);
        $method3 = $createMethod($m3);
        $class1 = $createClass(array($method1, $method2), $file1);
        $class2 = $createClass(array($method3), $file2);
        $astNamespaceMock = $this->createMock(ASTNamespace::class);
        $astNamespaceMock->method('getClasses')->willReturn(array($class1, $class2));

        $expectedResult = array(
            array($file1, array($m1, $m2)),
            array($file2, array($m3))
        );

        $result = iterator_to_array(filenameToMethods($astNamespaceMock));
        $resultWithMethodNames = array_map(
            fn ($t) => array($t[0], array_map(
                fn ($e) => $e->getName(),
                $t[1]
            )),
            $result
        );
        $this->assertEquals($expectedResult, $resultWithMethodNames);
    }
}
