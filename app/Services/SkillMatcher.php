<?php

namespace App\Services;

use App\Models\Job;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Support\Collection;

class SkillMatcher
{
    public function allSkillsFor(Job $job): array
    {
        $stored = $this->skillLabels($job->all_skills ?? []);

        if ($stored !== []) {
            return $this->uniqueLabels($stored);
        }

        return $this->knownSkills()
            ->filter(fn (string $skill): bool => $this->jobTextContains($job, $skill))
            ->values()
            ->all();
    }

    public function matchingSkillsFor(Job $job): array
    {
        $stored = $this->skillLabels($job->matching_skills ?? []);

        if ($stored !== []) {
            return $this->uniqueLabels($stored);
        }

        $profileSkills = $this->profileSkills()->map(fn (string $skill): string => mb_strtolower($skill));

        return collect($this->allSkillsFor($job))
            ->filter(fn (string $skill): bool => $profileSkills->contains(mb_strtolower($skill)))
            ->values()
            ->all();
    }

    public function missingSkillsFor(Job $job): array
    {
        $stored = $this->skillLabels($job->missing_skills ?? []);

        if ($stored !== []) {
            return $this->uniqueLabels($stored);
        }

        $profileSkills = $this->profileSkills()->map(fn (string $skill): string => mb_strtolower($skill));

        return collect($this->allSkillsFor($job))
            ->reject(fn (string $skill): bool => $profileSkills->contains(mb_strtolower($skill)))
            ->values()
            ->all();
    }

    public function skillLabels(array $skills): array
    {
        return collect($skills)
            ->map(function (mixed $skill): string {
                if (is_string($skill)) {
                    return $skill;
                }

                if (! is_array($skill)) {
                    return '';
                }

                return $skill['job_description_skill']
                    ?? $skill['my_skill']
                    ?? $skill['closest_candidate_skill']
                    ?? '';
            })
            ->map(fn (string $skill): string => trim($skill))
            ->filter()
            ->values()
            ->all();
    }

    public function knownSkills(): Collection
    {
        return Skill::query()
            ->pluck('name')
            ->merge(Job::query()->pluck('all_skills')->flatten(1)->filter(fn ($skill) => is_string($skill)))
            ->map(fn (string $skill): string => trim($skill))
            ->filter()
            ->unique(fn (string $skill): string => mb_strtolower($skill))
            ->values();
    }

    public function profileSkills(): Collection
    {
        $user = User::query()->orderBy('created_at')->first();

        if (! $user) {
            return collect();
        }

        return $user->skillCategories()
            ->with('skills')
            ->get()
            ->flatMap(fn ($category): Collection => $category->skills->pluck('name'))
            ->map(fn (string $skill): string => trim($skill))
            ->filter()
            ->unique(fn (string $skill): string => mb_strtolower($skill))
            ->values();
    }

    private function uniqueLabels(array $labels): array
    {
        return collect($labels)
            ->map(fn (string $label): string => trim($label))
            ->filter()
            ->unique(fn (string $label): string => mb_strtolower($label))
            ->values()
            ->all();
    }

    private function jobTextContains(Job $job, string $skill): bool
    {
        $needle = mb_strtolower(trim($skill));

        if (mb_strlen($needle) < 2) {
            return false;
        }

        $text = mb_strtolower(collect([
            $job->title,
            $job->search_keyword,
            $job->raw_job_description,
        ])->filter()->join(' '));

        return (bool) preg_match('/(^|[^a-z0-9+#.])'.preg_quote($needle, '/').'(?=$|[^a-z0-9+#.])/i', $text);
    }
}
