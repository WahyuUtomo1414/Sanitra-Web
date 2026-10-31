<?php

namespace App\Mail;

use App\Models\MsCompany;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Storage;

abstract class SanitraMail extends Mailable
{
    protected MsCompany $company;

    protected ?string $logoData = null;

    public function __construct()
    {
        $this->company = MsCompany::query()->oldest()->firstOrFail();

        $logoPath = $this->company->logo;

        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            $mime = Storage::disk('public')->mimeType($logoPath) ?: 'image/png';
            $this->logoData = 'data:'.$mime.';base64,'.base64_encode(
                Storage::disk('public')->get($logoPath)
            );
        }
    }
}
