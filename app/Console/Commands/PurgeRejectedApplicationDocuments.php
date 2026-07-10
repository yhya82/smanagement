<?php

namespace App\Console\Commands;

use App\Jobs\PurgeRejectedApplicationDocumentsJob;
use Illuminate\Console\Command;

class PurgeRejectedApplicationDocuments extends Command
{
    protected $signature = 'applications:purge-documents';

    protected $description = 'Purge documents and guardian PII for applications rejected more than 90 days ago';

    public function handle(): void
    {
        PurgeRejectedApplicationDocumentsJob::dispatch();

        $this->info('Document purge job dispatched.');
    }
}
