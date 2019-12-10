<?php

namespace Codacy\PDepend;

require_once __DIR__ . '/../src/CodacyPDepend.php';

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
}
