<?php

namespace App\Models;

use App\Models\Concerns\HasUuidKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name'])]
class Skill extends Model
{
    use HasUuidKey;

    public $timestamps = false;

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(SkillCategory::class, 'skill_category_skills', 'skill_id', 'category_id')
            ->withPivot('position')
            ->orderByPivot('position');
    }
}
