<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
| Add this file to your project at routes/console.php
| Then set up a single cron entry on your server:
|
|   * * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
|
| On shared hosting (cPanel): Go to Cron Jobs, add:
|   * * * * * /usr/bin/php /home/username/public_html/artisan schedule:run >> /dev/null 2>&1
|
*/

// Check for due scheduled emails every minute
Schedule::command('emails:process-scheduled')->everyMinute();

// Check for due scheduled campaigns every minute
Schedule::command('campaigns:process-scheduled')->everyMinute();

// Prune old webhook events older than 30 days
Schedule::command('model:prune', ['--model' => 'App\Models\WebhookEvent'])->daily();
