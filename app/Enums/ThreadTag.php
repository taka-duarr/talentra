<?php

namespace App\Enums;

enum ThreadTag: string
{
    case Tanya = 'tanya';
    case DiskusiSoal = 'diskusi-soal';
    case Tips = 'tips';
    case Info = 'info';
    case Diskusi = 'diskusi';

    public function label(): string
    {
        return match ($this) {
            self::Tanya => 'Tanya',
            self::DiskusiSoal => 'Diskusi soal',
            self::Tips => 'Tips',
            self::Info => 'Info',
            self::Diskusi => 'Obrolan',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Tanya => 'bg-sky-100 text-sky-700',
            self::DiskusiSoal => 'bg-brand-50 text-brand-700',
            self::Tips => 'bg-sun-100 text-sun-600',
            self::Info => 'bg-navy-50 text-navy-600',
            self::Diskusi => 'bg-coral-100 text-coral-600',
        };
    }
}
