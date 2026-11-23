<?php

namespace App\Enums;

enum QuestionType: string
{
    case SingleChoice = 'single_choice';
    case MultipleChoice = 'multiple_choice';
    case TrueFalse = 'true_false';
    case ShortAnswer = 'short_answer';
    case Weighted = 'weighted';

    public function label(): string
    {
        return match ($this) {
            self::SingleChoice => 'Pilihan ganda',
            self::MultipleChoice => 'Pilihan ganda kompleks',
            self::TrueFalse => 'Benar/salah per pernyataan',
            self::ShortAnswer => 'Isian singkat',
            self::Weighted => 'Berbobot per opsi (TKP)',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::SingleChoice => 'Pilih satu jawaban.',
            self::MultipleChoice => 'Pilih semua jawaban yang benar.',
            self::TrueFalse => 'Tentukan benar atau salah untuk setiap pernyataan.',
            self::ShortAnswer => 'Tulis jawaban singkat.',
            self::Weighted => 'Pilih satu jawaban yang paling sesuai.',
        };
    }

    public function usesOptions(): bool
    {
        return $this !== self::ShortAnswer;
    }
}
