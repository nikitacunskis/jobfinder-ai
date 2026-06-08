<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Job;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportJobs extends Command
{
    protected $signature = 'jobsucher:import-jobs {path=public/jobs.json}';

    protected $description = 'Import LinkedIn job research JSON into PostgreSQL.';

    public function handle(): int
    {
        $path = base_path($this->argument('path'));

        if (! file_exists($path)) {
            $this->error("Jobs JSON not found: {$path}");

            return self::FAILURE;
        }

        $jobs = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        $seenCompanies = 0;
        $upsertedJobs = 0;

        DB::transaction(function () use ($jobs, &$seenCompanies, &$upsertedJobs): void {
            foreach ($jobs as $rawJob) {
                $companyName = trim($this->text($rawJob['company']['name'] ?? null, 'UNKNOWN COMPANY')) ?: 'UNKNOWN COMPANY';
                $company = Company::query()->firstOrCreate(
                    ['name' => $companyName],
                    ['industry' => $this->text($rawJob['company']['industry'] ?? null)],
                );

                if ($company->industry === '' && filled($rawJob['company']['industry'] ?? '')) {
                    $company->update(['industry' => $this->text($rawJob['company']['industry'])]);
                }

                $seenCompanies++;

                Job::query()->updateOrCreate(
                    ['job_hash' => $this->text($rawJob['job_hash'] ?? null)],
                    [
                        'company_id' => $company->id,
                        'search_keyword' => $this->text($rawJob['search_keyword'] ?? null),
                        'language' => $this->text($rawJob['language'] ?? null),
                        'title' => $this->text($rawJob['job_title'] ?? null),
                        'link' => $this->text($rawJob['job_link'] ?? null),
                        'country' => $this->text($rawJob['location']['country'] ?? null),
                        'city' => $this->text($rawJob['location']['city'] ?? null),
                        'remote_type' => $this->text($rawJob['location']['remote_type'] ?? null, 'UNKNOWN'),
                        'origin_currency' => $this->text($rawJob['salary']['origin_currency'] ?? null, '-NOT MENTIONED-'),
                        'salary_original' => $this->text($rawJob['salary']['salary_original'] ?? null, '-NOT MENTIONED-'),
                        'eur_month_min' => $this->numberOrNull($rawJob['salary']['eur_month_min'] ?? null),
                        'eur_month_max' => $this->numberOrNull($rawJob['salary']['eur_month_max'] ?? null),
                        'posted_date' => $this->text($rawJob['job_meta']['posted_date'] ?? null),
                        'applicant_count' => $this->text($rawJob['job_meta']['applicant_count'] ?? null),
                        'easy_apply' => (bool) ($rawJob['job_meta']['easy_apply'] ?? false),
                        'employment_type' => $this->text($rawJob['job_meta']['employment_type'] ?? null),
                        'poster_name' => $this->text($rawJob['poster']['name'] ?? null),
                        'poster_position' => $this->text($rawJob['poster']['position'] ?? null),
                        'poster_type' => $this->text($rawJob['poster']['type'] ?? null, 'UNKNOWN'),
                        'all_skills' => $rawJob['skills']['all_skills'] ?? [],
                        'matching_skills' => $rawJob['skills']['matching_skills'] ?? [],
                        'missing_skills' => $rawJob['skills']['missing_skills'] ?? [],
                        'raw_job_description' => $this->text($rawJob['raw_job_description'] ?? null),
                        'raw_payload' => $rawJob,
                    ],
                );

                $upsertedJobs++;
            }
        });

        $this->info("Imported {$upsertedJobs} jobs from {$seenCompanies} company rows.");

        return self::SUCCESS;
    }

    private function text(mixed $value, string $fallback = ''): string
    {
        return (string) ($value ?? $fallback);
    }

    private function numberOrNull(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }
}
