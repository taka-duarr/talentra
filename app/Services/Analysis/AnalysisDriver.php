<?php

namespace App\Services\Analysis;

interface AnalysisDriver
{
    /**
     * Produces the analysis for one attempt.
     *
     * @param  array<string, mixed>  $context  output of AnalysisContextBuilder::build()
     */
    public function analyze(array $context): AnalysisResult;
}
