<?php

namespace App\Observers;

use App\Models\MsCompany;
use App\Models\MsEmployee;
use Illuminate\Validation\ValidationException;

class MsEmployeeObserver
{
    public function saving(MsEmployee $msEmployee): void
    {
        if ($msEmployee->exists && $msEmployee->isDirty('company_id') && MsCompany::query()
            ->where('pic_employee_id', $msEmployee->getKey())
            ->whereKeyNot($msEmployee->company_id)
            ->exists()) {
            throw ValidationException::withMessages(['company_id' => 'Karyawan ini masih menjadi PIC perusahaan lain.']);
        }
    }
}
