<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Đăng ký các command Artisan.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }

    /**
     * Lịch trình chạy tự động.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Train mô hình AI dự báo chi tiêu theo ngày
        $schedule->command('ai:train-daily')->dailyAt('00:45');

        // Train mô hình AI dự báo chi tiêu theo tháng
        $schedule->command('ai:train-expense')->dailyAt('01:00');
    }
}
