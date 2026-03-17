<?php

namespace App\Console\Commands;

use App\Jobs\SendCampaignJob;
use App\Models\Campaign;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class ProcessScheduledCampaigns extends Command
{
    protected $signature   = 'campaigns:process-scheduled';
    protected $description = 'Dispatch queued jobs for campaigns whose scheduled_at has passed';

    public function handle(): int
    {
        $due = Campaign::where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->whereNotNull('contact_list_id')
            ->get();

        if ($due->isEmpty()) {
            $this->info('No scheduled campaigns due.');
            return 0;
        }

        foreach ($due as $campaign) {
            $contacts = $campaign->contactList->activeContacts()->get();

            if ($contacts->isEmpty()) {
                $campaign->update(['status' => 'failed']);
                $this->warn("Campaign #{$campaign->id} \"{$campaign->name}\" has no active contacts — marked failed.");
                continue;
            }

            $campaign->update([
                'status'           => 'sending',
                'total_recipients' => $contacts->count(),
            ]);

            $jobs = $contacts->map(fn($c) => new SendCampaignJob($campaign->id, $c->id));

            Bus::batch($jobs)
                ->name("Campaign: {$campaign->name}")
                ->finally(function () use ($campaign) {
                    $campaign->refresh();
                    if ($campaign->status === 'sending') {
                        $campaign->update(['status' => 'sent', 'sent_at' => now()]);
                    }
                })
                ->onQueue('campaigns')
                ->dispatch();

            $this->info("✅ Dispatched {$contacts->count()} jobs for campaign \"{$campaign->name}\".");
        }

        return 0;
    }
}
