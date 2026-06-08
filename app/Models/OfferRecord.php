<?php

namespace App\Models;

use App\Enums\CompanyStatus;
use App\Models\Concerns\HasUuidKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['company_id', 'job_id', 'status', 'amount_money', 'documents', 'notes', 'declined_at'])]
class OfferRecord extends Model
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
        static::created(function (OfferRecord $offerRecord): void {
            $status = CompanyStatus::tryFrom($offerRecord->status);

            if ($status) {
                $offerRecord->company?->markStatus($status);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'declined_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
