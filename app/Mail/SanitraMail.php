<?php

namespace App\Mail;

use App\Models\MsCompany;
use Illuminate\Mail\Mailable;

abstract class SanitraMail extends Mailable
{
    protected MsCompany $company;

    public function __construct()
    {
        $this->company = MsCompany::query()->oldest()->firstOrFail();
    }
}
