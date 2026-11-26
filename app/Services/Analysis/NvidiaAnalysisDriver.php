<?php

namespace App\Services\Analysis;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Structured attempt analysis through NVIDIA NIM (OpenAI-compatible chat completions).
 * Tries each configured model in order; throws when all fail so the caller can fall
 * back to the rule-based driver.
 */
class NvidiaAnalysisDriver implements AnalysisDriver
{
    public function analyze(array $context): AnalysisResult
    {
        $config = config('talentra.analysis.nvidia');
        $models = array_values(array_filter(array_map('trim', (array) $config['models'])));

        if (blank($config['api_key']) || $models === []) {
            throw new RuntimeException('NVIDIA API key atau model belum dikonfigurasi.');
        }

        $lastError = null;

        foreach ($models as $model) {
            try {
                return $this->ask($model, $context, $config);
            } catch (Throwable $exception) {
                $lastError = $exception;
                Log::info('NVIDIA analysis model gagal, mencoba model berikutnya', ['model' => $model, 'error' => $exception->getMessage()]);
            }
        }

        throw new RuntimeException('Semua model NVIDIA gagal: '.$lastError?->getMessage(), previous: $lastError);
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $config
     */
    private function ask(string $model, array $context, array $config): AnalysisResult
    {
        $response = Http::withToken($config['api_key'])
            ->acceptJson()
            ->asJson()
            ->timeout((int) $config['timeout'])
            ->retry(2, 2000, fn (Throwable $exception) => $exception instanceof RequestException
                && in_array($exception->response->status(), [429, 500, 502, 503, 504], true), throw: false)
            ->post(rtrim($config['base_url'], '/').'/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => AnalysisPrompt::SYSTEM],
                    ['role' => 'user', 'content' => AnalysisPrompt::userMessage($context)],
                ],
                'temperature' => 0.4,
                'max_tokens' => (int) $config['max_tokens'],
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => ['name' => 'talentra_analysis', 'schema' => AnalysisPrompt::schema(), 'strict' => true],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException("HTTP {$response->status()}: ".mb_substr($response->body(), 0, 200));
        }

        $choice = $response->json('choices.0');

        if (($choice['finish_reason'] ?? null) === 'length') {
            throw new RuntimeException('Jawaban model terpotong (batas token).');
        }

        $data = $this->decode((string) ($choice['message']['content'] ?? ''));

        if (! AnalysisPrompt::isValid($data)) {
            throw new RuntimeException('Format analisis dari model tidak valid.');
        }

        return new AnalysisResult(
            data: AnalysisPrompt::normalize($data),
            source: 'ai',
            model: (string) ($response->json('model') ?? $model),
            inputTokens: (int) $response->json('usage.prompt_tokens', 0),
            outputTokens: (int) $response->json('usage.completion_tokens', 0),
        );
    }

    /**
     * Some models wrap JSON in reasoning tags or code fences despite the schema.
     */
    private function decode(string $text): mixed
    {
        $text = preg_replace('/<think>.*?<\/think>/s', '', $text) ?? $text;
        $text = trim(preg_replace('/^```(?:json)?\s*|\s*```$/', '', trim($text)) ?? $text);

        return json_decode($text, true);
    }
}
