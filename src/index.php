<?php

namespace Codacy\PDepend;

require_once __DIR__ . '/../vendor/autoload.php';
require_once 'CodacyConfiguration.php';
require_once 'CodacyReportGenerator.php';
require_once 'CodacyResult.php';
require_once 'CodacyPDepend.php';

use PDepend\Application;

try {
    error_reporting(E_ERROR);
    $app = new Application();
    $engine = $app->getEngine();
    $codacyReportGenerator = new CodacyReportGenerator();
    $engine->addReportGenerator($codacyReportGenerator);
    addFilesToEngine($engine, filesFromConfiguration());
    $result = $engine->analyze();
    $cyclomaticAnalyzer = $codacyReportGenerator->getCyclomaticComplexityAnalyzer();
    $nodeLocAnalyzer = $codacyReportGenerator->getNodeLocAnalyzer();
    $nodeMetrics = filesToNodeMetrics($result, $nodeLocAnalyzer);
    $filesToNrClasses = filesToNrClasses($result);
    $filesToNrMethods = filesToNrMethods($result);

    $content = resultToContent($result);
    $fileToLineComplexities = contentToFileComplexities($content, $cyclomaticAnalyzer);
    $filesWithResults = array_unique(array_merge(array_keys($filesToNrClasses), array_keys($filesToNrMethods)));
    foreach ($filesWithResults as $file) {
        $lineComplexities = $fileToLineComplexities[$file] ?: [];
        $complexity = empty($lineComplexities) ? 0 : max(array_map(
            fn ($lineComplexity) => $lineComplexity->getValue(),
            $lineComplexities
        ));
        $nrClasses = $filesToNrClasses[$file] ?: 0;
        $nrMethods = $filesToNrMethods[$file] ?: 0;

        $loc = $nodeMetrics[$file]["loc"] ?: 0;
        $cloc = $nodeMetrics[$file]["cloc"] ?: 0;

        $fileRelativeToSrc = stripStringPrefix($file, "/src/");

        $codacyResult = new CodacyResult($fileRelativeToSrc, $complexity, $loc, $cloc, $nrMethods, $nrClasses, $lineComplexities);
        print json_encode($codacyResult, JSON_UNESCAPED_SLASHES) . PHP_EOL;
    }
} catch (\Exception $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    fwrite(STDERR, $e->getTraceAsString() . PHP_EOL);
    exit(1);
}
