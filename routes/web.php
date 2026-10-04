<?php

use App\Exports\MasterDataTemplateExport;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;

Route::redirect('/', '/dashboard/login');

Route::get('/import/template/{entity}', function (string $entity) {
    abort_if(! auth()->check(), 401);

    $allowed = ['company', 'employee', 'client', 'item_category', 'item', 'unit', 'bank_account'];
    abort_if(! in_array($entity, $allowed, true), 404);

    $labels = [
        'company' => 'template-perusahaan',
        'employee' => 'template-karyawan',
        'client' => 'template-client',
        'item_category' => 'template-kategori-item',
        'item' => 'template-item',
        'unit' => 'template-satuan',
        'bank_account' => 'template-rekening-bank',
    ];

    return Excel::download(
        MasterDataTemplateExport::forEntity($entity),
        $labels[$entity].'.xlsx',
    );
})->middleware(['web', 'auth'])->name('import.template');
