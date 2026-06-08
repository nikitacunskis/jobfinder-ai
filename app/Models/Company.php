<?php

namespace App\Models;

use App\Enums\CompanyStatus;
use App\Models\Concerns\HasUuidKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

#[Fillable(['name', 'industry', 'status', 'status_updated_at'])]
class Company extends Model
{
    use HasUuidKey;

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }

    public function contactLogs(): HasMany
    {
        return $this->hasMany(ContactLog::class);
    }

    public function offerRecords(): HasMany
    {
        return $this->hasMany(OfferRecord::class);
    }

    public function favorites(): MorphMany
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }

    public function favoritedByUsers(): MorphToMany
    {
        return $this->morphToMany(User::class, 'favoritable', 'favorites')
            ->withTimestamps();
    }

    public function isFavoritedBy(?User $user): bool
    {
        return $user !== null && $user->hasFavorited($this);
    }

    public function markStatus(CompanyStatus $status): void
    {
        $this->forceFill([
            'status' => $status,
            'status_updated_at' => now(),
        ])->save();
    }

    protected function casts(): array
    {
        return [
            'status' => CompanyStatus::class,
            'status_updated_at' => 'datetime',
        ];
    }
}
