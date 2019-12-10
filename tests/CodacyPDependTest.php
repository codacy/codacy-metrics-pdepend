<?php

namespace Codacy\PDepend;

require_once __DIR__ . '/../src/CodacyPDepend.php';

use PDepend\Metrics\Analyzer\CyclomaticComplexityAnalyzer;
use PDepend\Source\AST\ASTClass;
use PDepend\Source\AST\ASTCompilationUnit;
use PDepend\Source\AST\ASTFunction;
use PDepend\Source\AST\ASTMethod;
use PDepend\Source\AST\ASTNamespace;
use PHPUnit\Framework\TestCase;

final class CodacyPDependTest extends TestCase
{
    private $file1 = 'file1.php';
    private $file2 = 'file2.php';

    function testArraysOfTuplesToMap()
    {
        $input1 = [
            ["abc", [1, 2]],
            ["b", [1]],
            ["b", [2]]
        ];

        $input2 = [
            ["a", [1, 2]],
            ["abc", [3]],
            ["b", [7]]
        ];

        $expectedResult = [
            "a" => [1, 2],
            "abc" => [1, 2, 3],
            "b" => [1, 2, 7]
        ];
        $result = arraysOfTuplesToMap($input1, $input2);
        $this->assertEquals($result, $expectedResult);
    }

    function testArraysOfTuplesToMapDuplicatedValues()
    {
        $input = [
            ["a", [1, 2]],
            ["a", [1, 2]]
        ];

        $expectedResult = [
            "a" => [1, 2, 1, 2]
        ];
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
        $f1 = 'f1';
        $f2 = 'f2';
        $f3 = 'f3';
        $function1 = $createFunction($f1, $this->file1);
        $function2 = $createFunction($f2, $this->file1);
        $function3 = $createFunction($f3, $this->file2);
        $astNamespaceMock = $this->createMock(ASTNamespace::class);
        $astNamespaceMock->method('getFunctions')->willReturn([$function1, $function2, $function3]);

        $expectedResult = [
            [$this->file1, [$f1]],
            [$this->file1, [$f2]],
            [$this->file2, [$f3]]
        ];

        $result = iterator_to_array(filenameToFunctions($astNamespaceMock));
        $resultWithFunctionNames = array_map(
            fn ($t) => [$t[0], array_map(
                fn ($e) => $e->getName(),
                $t[1]
            )],
            $result
        );
        $this->assertEquals($expectedResult, $resultWithFunctionNames);
    }

    function createMethod($name)
    {
        $method = $this->createMock(ASTMethod::class);
        $method->method('getName')->willReturn($name);
        return $method;
    }

    function createClass($filename, $methods = [])
    {
        $compilationUnit = $this->createMock(ASTCompilationUnit::class);
        $compilationUnit->method('getFileName')->willReturn($filename);
        $class = $this->createMock(ASTClass::class);
        $class->method('getCompilationUnit')->willReturn($compilationUnit);
        $class->method('getMethods')->willReturn($methods);
        return $class;
    }

    function testFilenameToMethods()
    {
        $m1 = 'm1';
        $m2 = 'm2';
        $m3 = 'm3';
        $method1 = $this->createMethod($m1);
        $method2 = $this->createMethod($m2);
        $method3 = $this->createMethod($m3);
        $class1 = $this->createClass($this->file1, [$method1, $method2]);
        $class2 = $this->createClass($this->file2, [$method3]);
        $astNamespaceMock = $this->createMock(ASTNamespace::class);
        $astNamespaceMock->method('getClasses')->willReturn([$class1, $class2]);

        $expectedResult = [
            [$this->file1, [$m1, $m2]],
            [$this->file2, [$m3]]
        ];

        $result = iterator_to_array(filenameToMethods($astNamespaceMock));
        $resultWithMethodNames = array_map(
            fn ($t) => [$t[0], array_map(
                fn ($e) => $e->getName(),
                $t[1]
            )],
            $result
        );
        $this->assertEquals($expectedResult, $resultWithMethodNames);
    }

    function testContentToFileComplexities()
    {
        $ccn = 1.0;
        $cyclomaticAnalyzer = $this->createMock(CyclomaticComplexityAnalyzer::class);
        $cyclomaticAnalyzer->method('getCcn')->withAnyParameters()->willReturn($ccn);

        $createNode = function ($class, $line) {
            $node = $this->createMock($class);
            $node->method('getStartLine')->willReturn($line);
            return $node;
        };

        $content = [
            $this->file1 => [$createNode(ASTMethod::class, 1), $createNode(ASTFunction::class, 5)],
            $this->file2 => [$createNode(ASTMethod::class, 7), $createNode(ASTMethod::class, 2)]
        ];

        $expectedResult = [
            $this->file1 => [new LineComplexity(1, $ccn), new LineComplexity(5, $ccn)],
            $this->file2 => [new LineComplexity(7, $ccn), new LineComplexity(2, $ccn)]
        ];

        $result = contentToFileComplexities($content, $cyclomaticAnalyzer);
        $this->assertEquals($expectedResult, $result);
    }

    function testFilesToNrClasses()
    {
        $classes = [
            $this->createClass($this->file1),
            $this->createClass($this->file2),
            $this->createClass($this->file2),
        ];

        $namespace = $this->createMock(ASTNamespace::class);
        $namespace->method('getClasses')->willReturn($classes);

        $expectedResult = [
            $this->file1 => 1,
            $this->file2 => 2
        ];

        $result = filesToNrClasses([$namespace]);
        $this->assertEquals($expectedResult, $result);
    }

    function testFilesToNrMethods()
    {
        $classes = [
            $this->createClass($this->file1, [$this->createMethod("m1")]),
            $this->createClass($this->file2, [$this->createMethod("m1"), $this->createMethod("m2")]),
            $this->createClass($this->file2, [$this->createMethod("m1"), $this->createMethod("m3"), $this->createMethod("m3")]),
        ];

        $namespace = $this->createMock(ASTNamespace::class);
        $namespace->method('getClasses')->willReturn($classes);

        $expectedResult = [
            $this->file1 => 1,
            $this->file2 => 2
        ];

        $result = filesToNrClasses([$namespace]);
        $this->assertEquals($expectedResult, $result);
    }
}
