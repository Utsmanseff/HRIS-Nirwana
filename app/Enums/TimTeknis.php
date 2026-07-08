<?php

namespace App\Enums;

enum TimTeknis: string
{
    case It = 'it';
    case Sarana = 'sarana';
    case Atem = 'atem';

    public function label(): string
    {
        return match ($this) {
            self::It => 'IT',
            self::Sarana => 'Sarana',
            self::Atem => 'ATEM',
        };
    }

    public function permission(): string
    {
        return match ($this) {
            self::It => 'kerjakan-tiket-it',
            self::Sarana => 'kerjakan-tiket-sarana',
            self::Atem => 'kerjakan-tiket-alkes',
        };
    }

    public static function dariPermission(string $permission): ?self
    {
        foreach (self::cases() as $t) {
            if ($t->permission() === $permission) {
                return $t;
            }
        }

        return null;
    }
}
