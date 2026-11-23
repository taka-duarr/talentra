<?php

namespace App\Enums;

enum ThreadScope: string
{
    case Global = 'global';
    case Category = 'category';
    case Material = 'material';

    public function label(): string
    {
        return match ($this) {
            self::Global => 'Forum umum',
            self::Category => 'Forum kategori',
            self::Material => 'Diskusi materi',
        };
    }
}
