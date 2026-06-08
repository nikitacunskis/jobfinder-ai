<?php

namespace App\Models;

use App\Enums\CompanyStatus;
use App\Models\Concerns\HasUuidKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['company_id', 'job_id', 'contact_type', 'contact_at', 'subject', 'message'])]
class ContactLog extends Model
{
    use HasUuidKey;

    public $timestamps = false;

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    protected static function booted(): void
    {
        static::created(function (ContactLog $contactLog): void {
            if ($contactLog->company?->status === CompanyStatus::Spotted) {
                $contactLog->company->markStatus(CompanyStatus::Contacted);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'contact_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
