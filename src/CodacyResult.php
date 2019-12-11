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

    public function __construct($line, $value)
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
    private $loc;
    private $cloc;
    private $nrMethods;
    private $nrClasses;
    private $lineComplexities;

    public function getFilename()
    {
        return $this->filename;
    }
    public function getComplexity()
    {
        return $this->complexity;
    }
    public function getLoc()
    {
        return $this->loc;
    }
    public function getCloc()
    {
        return $this->cloc;
    }
    public function getNrMethods()
    {
        return $this->nrMethods;
    }
    public function getNrClasses()
    {
        return $this->nrClasses;
    }
    public function getLineComplexities()
    {
        return $this->lineComplexities;
    }

    public function __construct($filename, $complexity, $loc, $cloc, $nrMethods, $nrClasses, $lineComplexities)
    {
        $this->filename = $filename;
        $this->complexity = $complexity;
        $this->loc = $loc;
        $this->cloc = $cloc;
        $this->nrMethods = $nrMethods;
        $this->nrClasses = $nrClasses;
        $this->lineComplexities = $lineComplexities;
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
