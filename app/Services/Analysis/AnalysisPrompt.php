<?php

namespace App\Services\Analysis;

/**
 * Instructions and output schema shared by every AI analysis driver,
 * so switching providers never changes what the student is told.
 */
final class AnalysisPrompt
{
    public const SYSTEM = <<<'PROMPT'
Kamu adalah tutor persiapan ujian di Talentra, platform latihan TKA, UTBK, CPNS, BUMN, dan Kedinasan di Indonesia.
Kamu menerima statistik satu kali pengerjaan try out (skor per bagian, penguasaan per topik, waktu, dan riwayat nilai) dan menulis analisis untuk peserta tersebut.

Aturan:
- Tulis dalam bahasa Indonesia yang hangat, lugas, dan spesifik. Sapa peserta dengan "kamu".
- Dasarkan setiap pernyataan hanya pada angka yang diberikan. Jangan mengarang skor, topik, atau materi yang tidak ada di data.
- Ringkasan berisi 2–3 kalimat: posisi nilai, bagian yang belum lolos ambang batas (jika ada), dan tren dibanding percobaan sebelumnya (jika ada).
- Kekuatan: topik dengan penguasaan tinggi. Kosongkan jika tidak ada.
- Kelemahan: maksimal 3 topik dengan penguasaan terendah, beserta kemungkinan penyebab yang masuk akal dari data (misalnya banyak soal kosong menandakan masalah waktu).
- Rekomendasi: tindakan konkret per topik lemah, gunakan nama topik persis seperti di data.
- Rencana belajar: 5–7 langkah harian yang realistis, kolom "day" ditulis "Hari 1", "Hari 2", dan seterusnya.
PROMPT;

    public static function userMessage(array $context): string
    {
        return "Statistik hasil try out (JSON):\n".json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * @return array<string, mixed>
     */
    public static function schema(): array
    {
        $string = ['type' => 'string'];
        $pair = fn (string $first, string $second): array => [
            'type' => 'object',
            'properties' => [$first => $string, $second => $string],
            'required' => [$first, $second],
            'additionalProperties' => false,
        ];

        return [
            'type' => 'object',
            'properties' => [
                'summary' => $string,
                'strengths' => ['type' => 'array', 'items' => $string],
                'weaknesses' => ['type' => 'array', 'items' => $pair('topic', 'reason')],
                'recommendations' => ['type' => 'array', 'items' => $pair('topic', 'action')],
                'study_plan' => ['type' => 'array', 'items' => $pair('day', 'task')],
            ],
            'required' => ['summary', 'strengths', 'weaknesses', 'recommendations', 'study_plan'],
            'additionalProperties' => false,
        ];
    }

    /**
     * Drops stray placeholders (comments, headings, blanks) some models emit inside
     * arrays and caps each list at the length the instructions ask for.
     *
     * @param  array<string, mixed>  $data
     * @return array{summary: string, strengths: list<string>, weaknesses: list<array{topic: string, reason: string}>, recommendations: list<array{topic: string, action: string}>, study_plan: list<array{day: string, task: string}>}
     */
    public static function normalize(array $data): array
    {
        $isText = fn (mixed $value): bool => is_string($value)
            && trim($value) !== ''
            && ! preg_match('#^\s*(/\*|//|\#|<!--)#', $value);

        $pairs = fn (array $items, string $first, string $second, int $limit): array => array_slice(array_values(array_map(
            fn (array $item): array => [$first => trim($item[$first]), $second => trim($item[$second])],
            array_filter($items, fn ($item) => is_array($item) && $isText($item[$first] ?? null) && $isText($item[$second] ?? null)),
        )), 0, $limit);

        return [
            'summary' => trim($data['summary']),
            'strengths' => array_slice(array_values(array_map('trim', array_filter($data['strengths'], $isText))), 0, 5),
            'weaknesses' => $pairs($data['weaknesses'], 'topic', 'reason', 3),
            'recommendations' => $pairs($data['recommendations'], 'topic', 'action', 5),
            'study_plan' => $pairs($data['study_plan'], 'day', 'task', 7),
        ];
    }

    /**
     * Whether decoded model output has every field the result page needs.
     */
    public static function isValid(mixed $data): bool
    {
        return is_array($data)
            && is_string($data['summary'] ?? null)
            && is_array($data['strengths'] ?? null)
            && is_array($data['weaknesses'] ?? null)
            && is_array($data['recommendations'] ?? null)
            && is_array($data['study_plan'] ?? null);
    }
}
