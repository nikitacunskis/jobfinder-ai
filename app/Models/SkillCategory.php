<?php

namespace App\Models;

use App\Models\Concerns\HasUuidKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['category_key', 'title'])]
class SkillCategory extends Model
{
    use HasUuidKey;

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'skill_category_skills', 'category_id', 'skill_id')
            ->withPivot('position')
            ->orderByPivot('position');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(SkillCategoryTranslation::class, 'category_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_skill_categories', 'category_id', 'user_id')
            ->withPivot('position')
            ->orderByPivot('position');
    }
}
