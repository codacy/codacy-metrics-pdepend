<?php

namespace CodacyPDepend;

use PDepend\Metrics\Analyzer\CyclomaticComplexityAnalyzer;
use PDepend\Report\ReportGenerator;

class CodacyReportGenerator implements ReportGenerator
{
    private $cyclomatic_analyzer = null;

    public function getCyclomaticAnalyzer()
    {
        return $this->cyclomatic_analyzer;
    }

    public function getAcceptedAnalyzers()
    {
        return array(
            'pdepend.analyzer.cyclomatic_complexity'
        );
    }

    public function log($analyzer)
    {
        if ($analyzer instanceof CyclomaticComplexityAnalyzer) {
            $this->cyclomatic_analyzer = $analyzer;
            return true;
        } else return false;
    }

    public function close()
    { }
}
