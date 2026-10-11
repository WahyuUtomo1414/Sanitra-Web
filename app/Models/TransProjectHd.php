<?php

namespace App\Models;

use App\Traits\AuditedBySoftDelete;
use Database\Factories\TransProjectHdFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransProjectHd extends Model
{
    /** @use HasFactory<TransProjectHdFactory> */
    use AuditedBySoftDelete, HasFactory, SoftDeletes;

    protected $table = 'trans_project_hd';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(TransSalesOrderHd::class, 'order_hd_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(MsClient::class, 'client_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(MsCompany::class, 'company_id');
    }

    public function projectType(): BelongsTo
    {
        return $this->belongsTo(MsProjectType::class, 'project_type_id');
    }

    public function picEmployee(): BelongsTo
    {
        return $this->belongsTo(MsEmployee::class, 'pic_employee_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(MsStatus::class, 'status_id');
    }

    public function stages(): HasMany
    {
        return $this->hasMany(TransProjectDt::class, 'project_hd_id');
    }

    public function monitoring(): HasMany
    {
        return $this->hasMany(TransProjectMonitoring::class, 'project_hd_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(TransSalesInvoiceHd::class, 'project_hd_id');
    }
}
