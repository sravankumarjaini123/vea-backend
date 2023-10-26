<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Schema;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('passport:purge')->daily();

        // Twitter - RefreshToken
        if (Schema::hasTable('users_twitters')) {
            $schedule->command('twitter:refreshToken')->everyMinute();
        }

        // Posts - Scheduler
        if (Schema::hasTable('posts')) {
            $schedule->command('post:update')->everyMinute();
        }

        // Logs - Scheduler
        if (Schema::hasTable('notifications')) {
            $schedule->command('sync:status')->everyMinute();
        }
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
