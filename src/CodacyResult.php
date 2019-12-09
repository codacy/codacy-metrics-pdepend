<?php

namespace Codacy\PDepend;

class LineComplexity implements \JsonSerializable
{
    private $line;
    private $value;

    public function getLine()
    {
        return $this->line;
    }

    public function getValue()
    {
        return $this->value;
    }

    function __construct($line, $value)
    {
        $this->line = $line;
        $this->value = $value;
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}

class CodacyResult implements \JsonSerializable
{
    private $filename;
    private $complexity;
    private $nrMethods;
    private $nrClasses;
    private $lineComplexities;

    function __construct($filename, $complexity, $nrMethods, $nrClasses, $lineComplexities)
    {
        $this->filename = $filename;
        $this->complexity = $complexity;
        $this->nrMethods = $nrMethods;
        $this->nrClasses = $nrClasses;
        $this->lineComplexities = $lineComplexities;
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
