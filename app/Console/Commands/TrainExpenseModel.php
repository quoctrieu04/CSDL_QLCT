<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class TrainExpenseModel extends Command
{
    protected $signature = 'ai:train-expense';
    protected $description = 'Retrain expense prediction model from v_training via external Python script';

    public function handle()
    {
        $this->info('🚀 Bắt đầu huấn luyện mô hình dự báo chi tiêu...');

        // 1) Đường dẫn Python & script (ưu tiên .env, fallback hợp lý)
        $pythonBin = env('PYTHON_BIN', 'python'); // Win có thể dùng 'py' hoặc đường dẫn tuyệt đối tới python.exe
        // Mặc định để script trong thư mục ai/ ngay dưới project Laravel
        $defaultScript = base_path('ai/train_model.py');
        $script = env('AI_TRAIN_SCRIPT_PATH', $defaultScript);

        if (!file_exists($script)) {
            $this->error("❌ Không tìm thấy script: {$script}");
            return self::FAILURE;
        }

        // 2) Chạy Python: set working dir = gốc project (để train_model.py đọc .env Laravel OK)
        $this->line("🐍 Python: {$pythonBin}");
        $this->line("📄 Script: {$script}");

        $process = new Process([$pythonBin, $script], base_path(), null, null, 900); // timeout 15'
        // Stream realtime output của Python
        $process->run(function ($type, $buffer) {
            echo $buffer;
        });

        if (!$process->isSuccessful()) {
            $this->error('⚠️ Huấn luyện thất bại!');
            $this->line($process->getErrorOutput());
            return self::FAILURE;
        }

        // 3) Kiểm tra file output
        $modelsDir = storage_path('app/models');
        $pklPath   = $modelsDir . DIRECTORY_SEPARATOR . 'linreg_monthly.pkl';
        $metaPath  = $modelsDir . DIRECTORY_SEPARATOR . 'linreg_meta.json';

        if (!file_exists($pklPath) || !file_exists($metaPath)) {
            $this->error('⚠️ Không tìm thấy file model/meta sau khi train.');
            $this->line('Kiểm tra lại script train_model.py và quyền ghi thư mục storage.');
            return self::FAILURE;
        }

        // 4) In tóm tắt kết quả từ meta
        try {
            $meta = json_decode(file_get_contents($metaPath), true, 512, JSON_THROW_ON_ERROR);
            $metrics = $meta['metrics'] ?? null;
            if ($metrics) {
                $this->info('✅ Huấn luyện thành công!');
                $this->line("📦 Model: {$pklPath}");
                $this->line("📝 Meta : {$metaPath}");
                $this->line(sprintf(
                    "📊 CV  — MAE: %.0f | RMSE: %.0f",
                    $metrics['cv_mae_mean'] ?? -1,
                    $metrics['cv_rmse_mean'] ?? -1
                ));
                $this->line(sprintf(
                    "🧪 Test — MAE: %.0f | RMSE: %.0f | n_test=%d",
                    $metrics['test_mae'] ?? -1,
                    $metrics['test_rmse'] ?? -1,
                    $metrics['n_test'] ?? -1
                ));
            } else {
                // Trường hợp baseline (ít dữ liệu) hoặc meta không có metrics
                $this->warn('ℹ️ Không thấy trường metrics trong meta (có thể rơi vào baseline).');
            }
        } catch (\Throwable $e) {
            $this->warn('ℹ️ Không đọc được meta.json: ' . $e->getMessage());
        }

        return self::SUCCESS;
    }
}
