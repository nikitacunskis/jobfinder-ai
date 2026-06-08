<?php

namespace App\Models;

use App\Models\Concerns\HasUuidKey;
use App\Services\SkillMatcher;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

#[Fillable([
    'job_hash',
    'company_id',
    'search_keyword',
    'language',
    'title',
    'link',
    'country',
    'city',
    'remote_type',
    'origin_currency',
    'salary_original',
    'eur_month_min',
    'eur_month_max',
    'posted_date',
    'applicant_count',
    'easy_apply',
    'employment_type',
    'poster_name',
    'poster_position',
    'poster_type',
    'all_skills',
    'matching_skills',
    'missing_skills',
    'raw_job_description',
    'raw_payload',
])]
class Job extends Model
{
    use HasUuidKey;

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
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

    public function salaryText(): string
    {
        return $this->salary_original && $this->salary_original !== '-NOT MENTIONED-'
            ? $this->salary_original
            : 'salary not mentioned';
    }

    public function locationText(): string
    {
        return collect([$this->city, $this->country])->filter()->join(', ') ?: 'Location unknown';
    }

    public function displayAllSkills(): array
    {
        return app(SkillMatcher::class)->allSkillsFor($this);
    }

    public function displayMatchingSkills(): array
    {
        return app(SkillMatcher::class)->matchingSkillsFor($this);
    }

    public function displayMissingSkills(): array
    {
        return app(SkillMatcher::class)->missingSkillsFor($this);
    }

    protected function casts(): array
    {
        return [
            'easy_apply' => 'boolean',
            'eur_month_min' => 'decimal:2',
            'eur_month_max' => 'decimal:2',
            'all_skills' => 'array',
            'matching_skills' => 'array',
            'missing_skills' => 'array',
            'raw_payload' => 'array',
        ];
    }
}
