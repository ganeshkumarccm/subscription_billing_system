<?php

namespace App\Console\Commands;

use App\Jobs\GenerateInvoiceJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

class DispatchCycleEndInvoicesCommand extends Command
{
    protected $signature = 'billing:dispatch-cycle-end-invoices';

    protected $description = 'Dispatch invoice generation jobs for subscriptions at cycle end';

    public function handle(): int
    {
        $today = now('UTC')->toDateString();
        $batches = 0;

        DB::table('subscriptions')
            ->where('status', 'active')
            ->whereDate('current_period_end', '<=', $today)
            ->orderBy('id')
            ->chunkById(500, function ($subscriptions) use (&$batches): void {
                $jobs = $subscriptions->map(
                    fn ($subscription) => new GenerateInvoiceJob(
                        (int) $subscription->id,
                        substr((string) $subscription->current_period_start, 0, 10),
                    )
                )->all();

                if ($jobs !== []) {
                    Bus::batch($jobs)
                        ->name('cycle-end-invoices')
                        ->onQueue('billing')
                        ->allowFailures()
                        ->dispatch();
                    $batches++;
                }
            });

        $this->info("Dispatched {$batches} invoice batches.");

        return self::SUCCESS;
    }
}
