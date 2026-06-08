<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Job;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Log;

class JobsucherSearch
{
    private const COMPANY_INDEX = 'jobsucher_companies';

    public function client(): Client
    {
        return ClientBuilder::create()
            ->setHosts([config('services.elasticsearch.url')])
            ->build();
    }

    public function reindexCompanies(): int
    {
        $client = $this->client();
        $client->indices()->delete(['index' => self::COMPANY_INDEX, 'ignore_unavailable' => true]);
        $client->indices()->create([
            'index' => self::COMPANY_INDEX,
            'body' => [
                'mappings' => [
                    'properties' => [
                        'id' => ['type' => 'keyword'],
                        'name' => ['type' => 'text', 'fields' => ['keyword' => ['type' => 'keyword']]],
                        'industry' => ['type' => 'text'],
                        'status' => ['type' => 'keyword'],
                        'job_titles' => ['type' => 'text'],
                        'job_skills' => ['type' => 'keyword'],
                        'job_skills_text' => ['type' => 'text'],
                        'job_descriptions' => ['type' => 'text'],
                        'jobs_count' => ['type' => 'integer'],
                        'contacts_count' => ['type' => 'integer'],
                        'offers_count' => ['type' => 'integer'],
                        'matching_skills_count' => ['type' => 'integer'],
                        'last_contact_at' => ['type' => 'date', 'ignore_malformed' => true],
                        'status_updated_at' => ['type' => 'date', 'ignore_malformed' => true],
                    ],
                ],
            ],
        ]);

        $operations = [];
        $count = 0;
        $skillMatcher = app(SkillMatcher::class);

        Company::query()
            ->with(['jobs', 'contactLogs', 'offerRecords'])
            ->chunkById(200, function ($companies) use (&$operations, &$count, $skillMatcher): void {
                foreach ($companies as $company) {
                    $skills = $company->jobs
                        ->flatMap(fn (Job $job): array => $skillMatcher->allSkillsFor($job))
                        ->unique(fn (string $skill): string => mb_strtolower($skill))
                        ->values()
                        ->all();

                    $operations[] = ['index' => ['_index' => self::COMPANY_INDEX, '_id' => $company->id]];
                    $operations[] = [
                        'id' => $company->id,
                        'name' => $company->name,
                        'industry' => $company->industry,
                        'status' => $company->status?->value ?? $company->status,
                        'job_titles' => $company->jobs->pluck('title')->implode(' '),
                        'job_skills' => $skills,
                        'job_skills_text' => implode(' ', $skills),
                        'job_descriptions' => $company->jobs->pluck('raw_job_description')->implode(' '),
                        'jobs_count' => $company->jobs->count(),
                        'contacts_count' => $company->contactLogs->count(),
                        'offers_count' => $company->offerRecords->count(),
                        'matching_skills_count' => $company->jobs->sum(fn (Job $job): int => count($skillMatcher->matchingSkillsFor($job))),
                        'last_contact_at' => optional($company->contactLogs->max('contact_at'))->toIso8601String(),
                        'status_updated_at' => optional($company->status_updated_at)->toIso8601String(),
                    ];
                    $count++;
                }
            });

        if ($operations !== []) {
            $this->client()->bulk(['refresh' => true, 'body' => $operations]);
        }

        return $count;
    }

    public function searchCompanyIds(?string $search, ?string $skill): ?array
    {
        $search = trim((string) $search);
        $skill = trim((string) $skill);

        if ($search === '' && $skill === '') {
            return null;
        }

        try {
            $must = [];

            if ($search !== '') {
                $must[] = [
                    'multi_match' => [
                        'query' => $search,
                        'fields' => ['name^5', 'job_titles^4', 'job_skills_text^3', 'job_descriptions'],
                    ],
                ];
            }

            if ($skill !== '') {
                $must[] = [
                    'bool' => [
                        'should' => [
                            ['term' => ['job_skills' => $skill]],
                            ['match' => ['job_skills_text' => $skill]],
                        ],
                        'minimum_should_match' => 1,
                    ],
                ];
            }

            $response = $this->client()->search([
                'index' => self::COMPANY_INDEX,
                'size' => 5000,
                'body' => ['query' => ['bool' => ['must' => $must]]],
            ]);

            return collect($response['hits']['hits'] ?? [])
                ->pluck('_id')
                ->values()
                ->all();
        } catch (\Throwable $error) {
            Log::warning('Elasticsearch company search failed', ['error' => $error->getMessage()]);

            return null;
        }
    }
}
