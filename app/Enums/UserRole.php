<?php

namespace App\Enums;

enum UserRole: string
{
    case User = 'user';
    case ContentAdmin = 'content_admin';
    case SuperAdmin = 'super_admin';

    public function label(): string
    {
        return match ($this) {
            self::User => 'Pengguna',
            self::ContentAdmin => 'Admin Konten',
            self::SuperAdmin => 'Super Admin',
        };
    }

    public function isAdmin(): bool
    {
        return $this !== self::User;
    }
}
