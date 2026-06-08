<?php

namespace App\Console\Commands;

use App\Services\JobsucherSearch;
use Illuminate\Console\Command;

class ReindexJobsucher extends Command
{
    protected $signature = 'jobsucher:reindex';

    protected $description = 'Rebuild the Jobsucher Elasticsearch indexes.';

    public function handle(JobsucherSearch $search): int
    {
        $count = $search->reindexCompanies();
        $this->info("Indexed {$count} companies.");

        return self::SUCCESS;
    }
}
