<?php

namespace Codacy\PDepend;

require_once __DIR__ . '/../src/CodacyPDepend.php';

use PDepend\Metrics\Analyzer\CyclomaticComplexityAnalyzer;
use PDepend\Metrics\Analyzer\NodeLocAnalyzer;
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

    public function createCompilationUnit($filename): ASTCompilationUnit
    {
        $res = $this->createMock(ASTCompilationUnit::class);
        $res->method('getFileName')->willReturn($filename);
        return $res;
    }
    public function createFunction($name, $filename): ASTFunction
    {
        $compilationUnit = $this->createCompilationUnit($filename);
        $f = $this->createMock(ASTFunction::class);
        $f->method('getCompilationUnit')->willReturn($compilationUnit);
        $f->method('getName')->willReturn($name);
        return $f;
    }

    public function createMethod($name): ASTMethod
    {
        $method = $this->createMock(ASTMethod::class);
        $method->method('getName')->willReturn($name);
        return $method;
    }

    public function createClass($filename, $methods = []): ASTClass
    {
        $compilationUnit = $this->createCompilationUnit($filename);
        $class = $this->createMock(ASTClass::class);
        $class->method('getCompilationUnit')->willReturn($compilationUnit);
        $class->method('getMethods')->willReturn($methods);
        return $class;
    }

    public function createNamespace($classes, $functions): ASTNamespace
    {
        $res = $this->createMock(ASTNamespace::class);
        $res->method('getClasses')->willReturn($classes);
        $res->method('getFunctions')->willReturn($functions);
        return $res;
    }

    public function testTuplesToMapWithArrayValues()
    {
        $input = [
            ["abc", 1],
            ["abc", 2],
            ["b", 1],
            ["b", 2],
            ["a", 1],
            ["a", 2],
            ["abc", 3],
            ["b", 7]
        ];

        $expectedResult = [
            "a" => [1, 2],
            "abc" => [1, 2, 3],
            "b" => [1, 2, 7]
        ];
        $result = tuplesToMapWithArrayValues($input);
        $this->assertEquals($result, $expectedResult);
    }

    public function testarrayOfArraysOfTuplesToMapDuplicatedValues()
    {
        $input = [
            ["a", 1],
            ["a", 2],
            ["a", 1],
            ["a", 2]
        ];

        $expectedResult = [
            "a" => [1, 2, 1, 2]
        ];
        $result = tuplesToMapWithArrayValues($input);
        $this->assertEquals($result, $expectedResult);
    }

    public function testFilenameToFunctions()
    {
        $f1 = 'f1';
        $f2 = 'f2';
        $f3 = 'f3';
        $function1 = $this->createFunction($f1, $this->file1);
        $function2 = $this->createFunction($f2, $this->file1);
        $function3 = $this->createFunction($f3, $this->file2);
        $namespace = $this->createNamespace([], [$function1, $function2, $function3]);

        $expectedResult = [
            [$this->file1, $f1],
            [$this->file1, $f2],
            [$this->file2, $f3]
        ];

        $result = iterator_to_array(filenameToFunctions($namespace), false);
        $resultWithFunctionNames = array_map(
            fn ($t) => [$t[0], $t[1]->getName()],
            $result
        );
        $this->assertEquals($expectedResult, $resultWithFunctionNames);
    }

    public function testFilenameToMethods()
    {
        $m1 = 'm1';
        $m2 = 'm2';
        $m3 = 'm3';
        $method1 = $this->createMethod($m1);
        $method2 = $this->createMethod($m2);
        $method3 = $this->createMethod($m3);
        $class1 = $this->createClass($this->file1, [$method1, $method2]);
        $class2 = $this->createClass($this->file2, [$method3]);
        $namespace = $this->createNamespace([$class1, $class2], []);

        $expectedResult = [
            [$this->file1, $m1],
            [$this->file1, $m2],
            [$this->file2, $m3]
        ];

        $result = iterator_to_array(filenameToMethods($namespace), false);
        $resultWithMethodNames = array_map(
            fn ($t) => [$t[0], $t[1]->getName()],
            $result
        );
        $this->assertEquals($expectedResult, $resultWithMethodNames);
    }

    public function testContentToFileComplexities()
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

    public function testFilesToNrClasses()
    {
        $namespacesClasses = [
            [
                $this->createClass($this->file1),
                $this->createClass($this->file2)
            ], [
                $this->createClass($this->file2)
            ]
        ];

        $namespaces = array_map(fn ($classes) => $this->createNamespace($classes, []), $namespacesClasses);

        $expectedResult = [
            $this->file1 => 1,
            $this->file2 => 2
        ];

        $result = filesToNrClasses($namespaces);
        $this->assertEquals($expectedResult, $result);
    }

    public function testFilesToNrMethods()
    {
        $namespacesClasses = [
            [
                $this->createClass($this->file1, [$this->createMethod("m1")]),
                $this->createClass($this->file2, [$this->createMethod("m1"), $this->createMethod("m2")])
            ],
            [
                $this->createClass($this->file2, [$this->createMethod("m1"), $this->createMethod("m3"), $this->createMethod("m3")])
            ],
        ];

        $namespaces = array_map(fn ($classes) => $this->createNamespace($classes, []), $namespacesClasses);

        $expectedResult = [
            $this->file1 => 1,
            $this->file2 => 2
        ];

        $result = filesToNrClasses($namespaces);
        $this->assertEquals($expectedResult, $result);
    }

    public function testFilesToNodeMetrics()
    {
        $f = $this->createFunction('f1', $this->file1);
        $c = $this->createClass($this->file2);

        $metricsFile1 = ['loc' => 10, 'cloc' => 1];
        $metricsFile2 = ['loc' => 50, 'cloc' => 20];

        $parameterReturnValueMap = [
            [$f->getCompilationUnit(), $metricsFile1],
            [$c->getCompilationUnit(), $metricsFile2]
        ];

        $nodeLocAnalyzer = $this->createMock(NodeLocAnalyzer::class);
        $nodeLocAnalyzer->method('getNodeMetrics')->will($this->returnValueMap($parameterReturnValueMap));

        $namespaces = [
            $this->createNamespace([$c], []),
            $this->createNamespace([], [$f])
        ];

        $result = filesToNodeMetrics($namespaces, $nodeLocAnalyzer);

        $expectedResult = [
            $this->file1 => $metricsFile1,
            $this->file2 => $metricsFile2
        ];

        $this->assertEquals($expectedResult, $result);
    }

    public function testResultToContent()
    {
        $f = $this->createFunction('f1', $this->file1);
        $m = $this->createMethod('m');
        $c = $this->createClass($this->file2, [$m]);

        $namespaces = [$this->createNamespace([$c], []), $this->createNamespace([], [$f])];

        $result = resultToContent($namespaces);

        $expectedResult = [
            $this->file1 => [$f],
            $this->file2 => [$m]
        ];

        $this->assertEquals($expectedResult, $result);
    }

    public function testStripStringPrefix()
    {
        $this->assertEquals("llo", stripStringPrefix("hello", "he"));
        $this->assertEquals("Hello world!", stripStringPrefix("Hello world!", "Bye"));
    }
}
