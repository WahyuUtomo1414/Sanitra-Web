<?php

namespace App\Enums;

enum EmployeePosition: string
{
    case Owner = 'Owner';
    case Marketing = 'Marketing';
    case Teknis = 'Teknis';
    case Finance = 'Finance';

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $position) {
            $options[$position->value] = $position->value;
        }

        return $options;
    }
}
