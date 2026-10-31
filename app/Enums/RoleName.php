<?php

namespace App\Enums;

enum RoleName: string
{
    case SuperAdmin = 'super_admin';
    case Marketing = 'Marketing';
    case Finance = 'Finance';
    case Teknis = 'Teknis';
}
