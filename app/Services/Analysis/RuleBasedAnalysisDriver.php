<?php

namespace App\Services\Analysis;

/**
 * Deterministic analysis used when no AI key is configured, the daily quota is used up,
 * or the AI request fails.
 */
class RuleBasedAnalysisDriver implements AnalysisDriver
{
    public function analyze(array $context): AnalysisResult
    {
        $topics = collect($context['topics']);
        $weak = $topics->filter(fn (array $topic) => $topic['percentage'] < 70)->sortBy('percentage')->take(3);
        $strong = $topics->filter(fn (array $topic) => $topic['percentage'] >= 80)->sortByDesc('percentage')->take(3);
        $failedSections = collect($context['sections'])->where('passed', false)->pluck('name');

        $summary = sprintf(
            'Kamu menjawab benar %d dari %d soal (%s%%).',
            $context['correct'],
            $context['correct'] + $context['wrong'] + $context['blank'],
            rtrim(rtrim(number_format($context['percentage'], 1, ',', ''), '0'), ','),
        );

        if ($failedSections->isNotEmpty()) {
            $summary .= ' Bagian '.$failedSections->join(', ', ' dan ').' masih di bawah ambang batas.';
        } elseif ($context['passed'] === true) {
            $summary .= ' Semua bagian sudah melewati ambang batas.';
        }

        $previous = collect($context['previous_percentages'])->last();
        if ($previous !== null) {
            $delta = round($context['percentage'] - $previous, 1);
            $summary .= $delta >= 0
                ? " Nilaimu naik {$delta} poin persen dari percobaan sebelumnya."
                : ' Nilaimu turun '.abs($delta).' poin persen dari percobaan sebelumnya.';
        }

        if ($context['blank'] > 0) {
            $summary .= " Ada {$context['blank']} soal yang tidak dijawab.";
        }

        $weaknesses = $weak->map(fn (array $topic) => [
            'topic' => $topic['topic'],
            'reason' => "Benar {$topic['correct']} dari {$topic['total']} soal ({$topic['percentage']}%).",
        ])->values()->all();

        $recommendations = $weak->map(fn (array $topic) => [
            'topic' => $topic['topic'],
            'action' => "Ulangi materi {$topic['topic']}, lalu kerjakan 10–20 soal latihan khusus topik ini.",
        ])->values()->all();

        if ($context['blank'] > 0) {
            $recommendations[] = [
                'topic' => 'Manajemen waktu',
                'action' => 'Latih tempo: tandai soal sulit sebagai ragu-ragu dan kembali setelah semua soal mudah dijawab.',
            ];
        }

        $plan = [];
        foreach (array_values($weaknesses) as $index => $weakness) {
            $plan[] = ['day' => 'Hari '.($index * 2 + 1), 'task' => 'Pelajari ulang materi '.$weakness['topic'].'.'];
            $plan[] = ['day' => 'Hari '.($index * 2 + 2), 'task' => 'Kerjakan latihan soal '.$weakness['topic'].' dan baca pembahasannya.'];
        }
        $plan[] = ['day' => 'Hari '.(count($plan) + 1), 'task' => 'Kerjakan try out lagi untuk mengukur perkembangan.'];

        return new AnalysisResult(
            data: [
                'summary' => $summary,
                'strengths' => $strong->map(fn (array $topic) => "{$topic['topic']} ({$topic['percentage']}% benar)")->values()->all(),
                'weaknesses' => $weaknesses,
                'recommendations' => $recommendations,
                'study_plan' => array_slice($plan, 0, 7),
            ],
            source: 'rules',
        );
    }
}
