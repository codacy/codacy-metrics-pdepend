<?php

namespace Codacy\PDepend;

use PDepend\Metrics\Analyzer;
use PDepend\Metrics\Analyzer\CyclomaticComplexityAnalyzer;
use PDepend\Metrics\Analyzer\NodeLocAnalyzer;
use PDepend\Report\ReportGenerator;

class CodacyReportGenerator implements ReportGenerator
{
    private ?CyclomaticComplexityAnalyzer $cyclomaticComplexityAnalyzer = null;
    private ?NodeLocAnalyzer $nodeLocAnalyzer = null;

    public function getCyclomaticComplexityAnalyzer()
    {
        return $this->cyclomaticComplexityAnalyzer;
    }

    public function getNodeLocAnalyzer()
    {
        return $this->nodeLocAnalyzer;
    }

    public function getAcceptedAnalyzers()
    {
        return [
            'pdepend.analyzer.cyclomatic_complexity',
            'pdepend.analyzer.node_loc'
        ];
    }

    public function log(Analyzer $analyzer)
    {
        if ($analyzer instanceof CyclomaticComplexityAnalyzer) {
            $this->cyclomaticComplexityAnalyzer = $analyzer;
            return true;
        } elseif ($analyzer instanceof NodeLocAnalyzer) {
            $this->nodeLocAnalyzer = $analyzer;
            return true;
        } else {
            return false;
        }
    }

    public function close()
    {
    }
}
