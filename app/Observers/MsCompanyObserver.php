<?php

namespace App\Observers;

use App\Models\MsCompany;
use App\Models\MsEmployee;
use Illuminate\Validation\ValidationException;

class MsCompanyObserver
{
    public function saving(MsCompany $msCompany): void
    {
        if ($msCompany->getKey() === 1) {
            if (! $msCompany->active || $msCompany->parent_id !== null) {
                throw ValidationException::withMessages(['active' => 'Perusahaan utama harus aktif dan tidak memiliki induk.']);
            }
        } else {
            if (! MsCompany::query()->whereKey(1)->exists()) {
                throw ValidationException::withMessages(['parent_id' => 'Perusahaan utama ID 1 harus tersedia lebih dulu.']);
            }

            $msCompany->parent_id = 1;
        }

        if ($msCompany->pic_employee_id !== null && ! MsEmployee::query()
            ->whereKey($msCompany->pic_employee_id)
            ->where('company_id', $msCompany->getKey())
            ->exists()) {
            throw ValidationException::withMessages(['pic_employee_id' => 'PIC harus karyawan dari perusahaan ini.']);
        }
    }

    public function deleting(MsCompany $msCompany): void
    {
        if ($msCompany->getKey() === 1) {
            throw ValidationException::withMessages(['company' => 'Perusahaan utama ID 1 tidak dapat dihapus.']);
        }
    }
}
