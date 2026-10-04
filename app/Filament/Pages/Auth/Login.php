<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    protected static string $layout = 'filament.layouts.auth';

    public function getHeading(): string|Htmlable|null
    {
        return 'Selamat Datang';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Silakan masukkan kredensial akun Anda untuk mengakses sistem.';
    }

    public function hasLogo(): bool
    {
        return false;
    }
}
