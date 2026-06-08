<?php

namespace App\Console\Commands;

use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\SkillCategoryTranslation;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ImportProfile extends Command
{
    protected $signature = 'jobsucher:import-profile {path=public/profile.json}';

    protected $description = 'Import the Jobsucher profile skill tree JSON into PostgreSQL.';

    public function handle(): int
    {
        $path = base_path($this->argument('path'));

        if (! file_exists($path)) {
            $this->error("Profile JSON not found: {$path}");

            return self::FAILURE;
        }

        $skillTree = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($skillTree): void {
            $user = User::query()->updateOrCreate(
                ['login' => 'admin'],
                [
                    'name' => 'Nikita Cunskis',
                    'email' => 'nikita@cunskis.lv',
                    'password' => Hash::make('admin'),
                    'skill_tree' => $skillTree,
                ],
            );

            DB::table('user_skill_categories')->where('user_id', $user->id)->delete();
            SkillCategoryTranslation::query()->where('user_id', $user->id)->delete();

            foreach (array_values(array_filter(array_keys($skillTree))) as $categoryPosition => $categoryKey) {
                $rawCategory = is_array($skillTree[$categoryKey] ?? null) ? $skillTree[$categoryKey] : [];
                $category = SkillCategory::query()->updateOrCreate(
                    ['category_key' => $categoryKey],
                    ['title' => (string) ($rawCategory['title'] ?? '')],
                );

                DB::table('user_skill_categories')->updateOrInsert(
                    ['user_id' => $user->id, 'category_id' => $category->id],
                    ['position' => $categoryPosition, 'created_at' => now()],
                );

                if (filled($rawCategory['translation'] ?? '')) {
                    SkillCategoryTranslation::query()->updateOrCreate(
                        ['user_id' => $user->id, 'category_id' => $category->id, 'locale' => 'lv'],
                        ['title' => (string) $rawCategory['translation']],
                    );
                }

                DB::table('skill_category_skills')->where('category_id', $category->id)->delete();

                foreach (array_values($rawCategory['skills'] ?? []) as $skillPosition => $skillName) {
                    $skillName = trim((string) $skillName);

                    if ($skillName === '') {
                        continue;
                    }

                    $skill = Skill::query()->updateOrCreate(['name' => $skillName]);
                    DB::table('skill_category_skills')->updateOrInsert(
                        ['category_id' => $category->id, 'skill_id' => $skill->id],
                        ['position' => $skillPosition, 'created_at' => now()],
                    );
                }
            }
        });

        $this->info('Imported profile skills.');

        return self::SUCCESS;
    }
}
