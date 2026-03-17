<?php

namespace App\Console\Commands;

use App\Jobs\SendScheduledEmailJob;
use App\Models\ScheduledEmail;
use Illuminate\Console\Command;

class ProcessScheduledEmails extends Command
{
    protected $signature   = 'emails:process-scheduled';
    protected $description = 'Dispatch queued jobs for individual scheduled emails whose send_at has passed';

    public function handle(): int
    {
        $due = ScheduledEmail::pending()->get();

        if ($due->isEmpty()) {
            $this->info('No scheduled emails due.');
            return 0;
        }

        foreach ($due as $email) {
            $email->update(['status' => 'processing']);
            SendScheduledEmailJob::dispatch($email->id)->onQueue('emails');
            $this->info("📤 Queued scheduled email #{$email->id} → {$email->to_email}");
        }

        $this->info("Total: {$due->count()} email(s) dispatched.");
        return 0;
    }
}
