<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [];

    protected function schedule(Schedule $schedule)
    {
        $schedule->command('accounts:purge-deleted')->dailyAt('03:10');
        $schedule->command('connections:remind-pending --limit=200')->hourly()->withoutOverlapping();
        // Hourly job enforces each user's configured reminder interval and maximum.
        $schedule->command('notifications:remind-unread --limit=500')->hourly()->withoutOverlapping();
        $schedule->command('referrals:award-verified --limit=500')->hourly()->withoutOverlapping();
        $schedule->command('notifications:send-digests --limit=200')->hourly()->withoutOverlapping();
        $schedule->command('rewards:award-milestones --limit=500')->dailyAt('04:30')->withoutOverlapping();
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
