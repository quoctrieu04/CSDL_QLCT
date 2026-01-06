<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class AIAlertExpense extends Command
{
    protected $signature = 'ai:alert-expense';
    protected $description = 'Chạy cảnh báo chi tiêu bằng AI';

    public function handle()
    {
        $python = env('PYTHON_BIN', 'python');
        $script = env('AI_ALERT_SCRIPT_PATH', base_path('AI/alert_generator.py'));

        $this->info("[AI] Chạy cảnh báo chi tiêu...");

        // ✅ Thêm chcp 65001 để bật UTF-8 trong terminal Windows
        $cmd = "chcp 65001>nul && {$python} \"{$script}\"";

        $process = Process::fromShellCommandline($cmd);
        $process->setTimeout(120);

        // In log trực tiếp từ Python ra màn hình
        $process->run(function ($type, $buffer) {
            echo $buffer;
        });

        if ($process->isSuccessful()) {
            $this->info("✅ Hoàn thành cảnh báo.");
        } else {
            $this->error("❌ Lỗi khi chạy cảnh báo.");
        }
    }
}
