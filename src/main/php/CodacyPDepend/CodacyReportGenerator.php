<?php

namespace CodacyPDepend;

use PDepend\Metrics\Analyzer;
use PDepend\Metrics\Analyzer\CyclomaticComplexityAnalyzer;
use PDepend\Report\ReportGenerator;

class CodacyReportGenerator implements ReportGenerator
{
    private ?CyclomaticComplexityAnalyzer $cyclomaticComplexityAnalyzer = null;

    public function getCyclomaticComplexityAnalyzer()
    {
        return $this->cyclomaticComplexityAnalyzer;
    }

    public function getAcceptedAnalyzers()
    {
        return array(
            'pdepend.analyzer.cyclomatic_complexity'
        );
    }

    public function log(Analyzer $analyzer)
    {
        if ($analyzer instanceof CyclomaticComplexityAnalyzer) {
            $this->cyclomaticComplexityAnalyzer = $analyzer;
            return true;
        } else return false;
    }

    public function close()
    { }
}
