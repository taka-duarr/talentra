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
    private const SYSTEM_PROMPT = <<<'PROMPT'
Kamu adalah tutor persiapan ujian di Talentra, platform latihan TKA, UTBK, CPNS, BUMN, dan Kedinasan di Indonesia.
Kamu menerima statistik satu kali pengerjaan try out (skor per bagian, penguasaan per topik, waktu, dan riwayat nilai) dan menulis analisis untuk peserta tersebut.

Aturan:
- Tulis dalam bahasa Indonesia yang hangat, lugas, dan spesifik. Sapa peserta dengan "kamu".
- Dasarkan setiap pernyataan hanya pada angka yang diberikan. Jangan mengarang skor, topik, atau materi yang tidak ada di data.
- Ringkasan berisi 2–3 kalimat: posisi nilai, bagian yang belum lolos ambang batas (jika ada), dan tren dibanding percobaan sebelumnya (jika ada).
- Kekuatan: topik dengan penguasaan tinggi. Kosongkan jika tidak ada.
- Kelemahan: maksimal 3 topik dengan penguasaan terendah, beserta kemungkinan penyebab yang masuk akal dari data (misalnya banyak soal kosong menandakan masalah waktu).
- Rekomendasi: tindakan konkret per topik lemah, gunakan nama topik persis seperti di data.
- Rencana belajar: 5–7 langkah harian yang realistis.
PROMPT;

    public function __construct(private ?Client $client = null) {}

    public function analyze(array $context): AnalysisResult
    {
        $config = config('talentra.analysis.anthropic');
        $client = $this->client ?? new Client(apiKey: $config['api_key']);

        $message = $client->beta->messages->create(
            maxTokens: $config['max_tokens'],
            messages: [[
                'role' => 'user',
                'content' => "Statistik hasil try out (JSON):\n".json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            ]],
            model: $config['model'],
            fallbacks: 'default',
            outputConfig: ['format' => ['type' => 'json_schema', 'schema' => $this->schema()]],
            system: self::SYSTEM_PROMPT,
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

        if (! is_array($data) || ! isset($data['summary'], $data['weaknesses'], $data['recommendations'])) {
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

    /**
     * @return array<string, mixed>
     */
    private function schema(): array
    {
        $string = ['type' => 'string'];

        return [
            'type' => 'object',
            'properties' => [
                'summary' => $string,
                'strengths' => ['type' => 'array', 'items' => $string],
                'weaknesses' => ['type' => 'array', 'items' => [
                    'type' => 'object',
                    'properties' => ['topic' => $string, 'reason' => $string],
                    'required' => ['topic', 'reason'],
                    'additionalProperties' => false,
                ]],
                'recommendations' => ['type' => 'array', 'items' => [
                    'type' => 'object',
                    'properties' => ['topic' => $string, 'action' => $string],
                    'required' => ['topic', 'action'],
                    'additionalProperties' => false,
                ]],
                'study_plan' => ['type' => 'array', 'items' => [
                    'type' => 'object',
                    'properties' => ['day' => $string, 'task' => $string],
                    'required' => ['day', 'task'],
                    'additionalProperties' => false,
                ]],
            ],
            'required' => ['summary', 'strengths', 'weaknesses', 'recommendations', 'study_plan'],
            'additionalProperties' => false,
        ];
    }
}
