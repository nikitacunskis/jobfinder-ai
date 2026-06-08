<?php

namespace App\Filament\Resources\Skills\Pages;

use App\Filament\Resources\Skills\SkillResource;
use App\Models\User;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;

class ViewProfileSkills extends Page
{
    private const CATEGORY_COLORS = [
        'gray',
        'danger',
        'warning',
        'success',
        'info',
        'primary',
    ];

    protected static string $resource = SkillResource::class;

    protected string $view = 'filament.resources.skills.pages.view-profile-skills';

    protected static ?string $title = 'Profile skills';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $user = auth()->user() instanceof User
            ? auth()->user()
            : User::query()->orderBy('created_at')->first();
        $categories = $this->profileSkillCategories($user);

        return [
            'categories' => $categories,
            'skillsCount' => $categories
                ->flatMap(fn (array $category): array => $category['skills'])
                ->unique(fn (string $skill): string => mb_strtolower($skill))
                ->count(),
        ];
    }

    private function profileSkillCategories(?User $user): Collection
    {
        if (! $user) {
            return collect();
        }

        return $user->skillCategories()
            ->with([
                'skills',
                'translations' => fn ($query) => $query->where('user_id', $user->id),
            ])
            ->get()
            ->map(function ($category): array {
                $translation = $category->translations->first();
                $title = $category->title ?: $category->category_key;

                return [
                    'title' => $title,
                    'translation' => $translation?->title,
                    'color' => $this->categoryColor($title),
                    'skills' => $category->skills
                        ->pluck('name')
                        ->map(fn (string $skill): string => trim($skill))
                        ->filter()
                        ->values()
                        ->all(),
                ];
            });
    }

    /**
     */
    private function categoryColor(string $title): string
    {
        return self::CATEGORY_COLORS[crc32($title) % count(self::CATEGORY_COLORS)];
    }
}
