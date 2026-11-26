<?php

namespace App\Services\Analysis;

use Anthropic\Client;
use RuntimeException;

/**
 * Asks Claude for a structured analysis of the attempt statistics.
 * Throws on any failure so the caller can fall back to the rule-based driver.
 */
class AnthropicAnalysisDriver implements AnalysisDriver
{
    public function __construct(private ?Client $client = null) {}

    public function analyze(array $context): AnalysisResult
    {
        $config = config('talentra.analysis.anthropic');
        $client = $this->client ?? new Client(apiKey: $config['api_key']);

        $message = $client->beta->messages->create(
            maxTokens: $config['max_tokens'],
            messages: [[
                'role' => 'user',
                'content' => AnalysisPrompt::userMessage($context),
            ]],
            model: $config['model'],
            fallbacks: 'default',
            outputConfig: ['format' => ['type' => 'json_schema', 'schema' => AnalysisPrompt::schema()]],
            system: AnalysisPrompt::SYSTEM,
            betas: ['server-side-fallback-2026-07-01'],
            requestOptions: ['timeout' => (float) $config['timeout'], 'maxRetries' => 1],
        );

        if ($message->stopReason === 'refusal') {
            throw new RuntimeException('Analisis ditolak oleh model.');
        }

        $text = null;
        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $text = $block->text;
                break;
            }
        }

        $data = is_string($text) ? json_decode($text, true) : null;

        if (! AnalysisPrompt::isValid($data)) {
            throw new RuntimeException('Format analisis dari model tidak valid.');
        }

        return new AnalysisResult(
            data: $data,
            source: 'ai',
            model: $message->model,
            inputTokens: (int) $message->usage->inputTokens,
            outputTokens: (int) $message->usage->outputTokens,
        );
    }
}
