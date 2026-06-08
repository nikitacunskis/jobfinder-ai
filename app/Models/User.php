<?php

namespace App\Models;

use App\Models\Concerns\HasUuidKey;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['login', 'name', 'email', 'password', 'skill_tree'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuidKey, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function skillCategories(): BelongsToMany
    {
        return $this->belongsToMany(SkillCategory::class, 'user_skill_categories', 'user_id', 'category_id')
            ->withPivot('position')
            ->orderByPivot('position');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function favoriteCompanies(): MorphToMany
    {
        return $this->morphedByMany(Company::class, 'favoritable', 'favorites')
            ->withTimestamps();
    }

    public function favoriteJobs(): MorphToMany
    {
        return $this->morphedByMany(Job::class, 'favoritable', 'favorites')
            ->withTimestamps();
    }

    public function hasFavorited(Model $favoritable): bool
    {
        return $this->favorites()
            ->where('favoritable_type', $favoritable->getMorphClass())
            ->where('favoritable_id', $favoritable->getKey())
            ->exists();
    }

    public function favorite(Model $favoritable): Favorite
    {
        return $this->favorites()->firstOrCreate([
            'favoritable_type' => $favoritable->getMorphClass(),
            'favoritable_id' => $favoritable->getKey(),
        ]);
    }

    public function unfavorite(Model $favoritable): void
    {
        $this->favorites()
            ->where('favoritable_type', $favoritable->getMorphClass())
            ->where('favoritable_id', $favoritable->getKey())
            ->delete();
    }

    public function toggleFavorite(Model $favoritable): bool
    {
        if ($this->hasFavorited($favoritable)) {
            $this->unfavorite($favoritable);

            return false;
        }

        $this->favorite($favoritable);

        return true;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'skill_tree' => 'array',
        ];
    }
}
