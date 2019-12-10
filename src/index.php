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
    addFilesFromConfiguration($engine);
    $result = $engine->analyze();
    $cyclomaticAnalyzer = $codacyReportGenerator->getCyclomaticComplexityAnalyzer();
    $content = resultToContent($result);
    $fileToLineComplexities = contentToFileComplexities($content, $cyclomaticAnalyzer);

    $filesToNrClasses = filesToNrClasses($result);
    $filesToNrMethods = filesToNrMethods($result);

    $files = array_unique(array_merge(array_keys($filesToNrClasses), (array_keys($filesToNrMethods))));

    foreach ($files as $file) {
        $lineComplexities = $fileToLineComplexities[$file] ?: array();

        $complexity = empty($lineComplexities) ? 0 : max(array_map(
            fn ($lineComplexity) => $lineComplexity->getValue(),
            $lineComplexities
        ));
        $nrClasses = $filesToNrClasses[$file] ?: 0;
        $nrMethods = $filesToNrMethods[$file] ?: 0;

        $fileRelativeToSrc = stripStringPrefix($file, "/src/");

        $codacyResult = new CodacyResult($fileRelativeToSrc, $complexity, $nrMethods, $nrClasses, $fileToLineComplexities[$file]);
        print(json_encode($codacyResult, JSON_UNESCAPED_SLASHES) . PHP_EOL);
    }
} catch (\Exception $e) {
    print($e->getMessage() . PHP_EOL);
    print($e->getTraceAsString() . PHP_EOL);
    exit(1);
}
