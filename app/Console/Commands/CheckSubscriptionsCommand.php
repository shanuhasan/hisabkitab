<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckSubscriptionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hisabkitab:check-subscriptions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check expired merchant subscriptions and update subscription statuses';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("Scanning expired merchant subscriptions...");

        $expiredSubs = Subscription::where('status', 'active')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now())
            ->get();

        $count = 0;
        foreach ($expiredSubs as $sub) {
            $sub->update(['status' => 'expired']);

            // Update associated tenant status
            $tenant = Tenant::find($sub->tenant_id);
            if ($tenant) {
                $tenant->update([
                    'subscription_status' => 'expired',
                ]);
            }

            $count++;
            $this->line("Subscription #{$sub->id} for Tenant #{$sub->tenant_id} marked as expired.");
            Log::info("Subscription #{$sub->id} for Tenant #{$sub->tenant_id} marked as expired.");
        }

        $this->info("Completed subscription scan. {$count} subscription(s) updated.");
        return Command::SUCCESS;
    }
}

