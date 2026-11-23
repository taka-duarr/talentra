<?php

namespace App\Enums;

enum ContentStatus: string
{
    case Draft = 'draft';
    case Review = 'review';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Review => 'Review',
            self::Published => 'Terbit',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'bg-navy-50 text-muted',
            self::Review => 'bg-sun-100 text-sun-600',
            self::Published => 'bg-brand-50 text-brand-700',
        };
    }
}
