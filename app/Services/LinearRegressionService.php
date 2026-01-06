<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class LinearRegressionService
{
    protected array $features = [];
    protected array $coefficients = [];
    protected float $intercept = 0.0;
    protected array $scalerMean = [];
    protected array $scalerScale = [];
    protected array $moneyCols = [];
    protected float $epsRatio = 1e-9;
    protected string $coeffPath;

    public function __construct()
    {
        $this->coeffPath = storage_path('app/models/linreg_coeffs.json');

        try {
            if (!is_file($this->coeffPath)) {
                Log::warning("LRService: Missing file {$this->coeffPath}");
                return;
            }

            $json = json_decode(file_get_contents($this->coeffPath), true) ?: [];

            if (
                !isset($json['features'], $json['coefficients'], $json['intercept'],
                        $json['scaler_mean'], $json['scaler_scale'])
            ) {
                Log::warning("LRService: Invalid or incomplete coeff JSON");
                return;
            }

            $this->features      = $json['features'];
            $this->coefficients  = array_map('floatval', $json['coefficients']);
            $this->intercept     = (float)$json['intercept'];
            $this->scalerMean    = array_map('floatval', $json['scaler_mean']);
            $this->scalerScale   = array_map('floatval', $json['scaler_scale']);
            $this->moneyCols     = $json['preprocess']['money_cols_log1p'] ?? [];
            $this->epsRatio      = (float)($json['preprocess']['epsilon_ratio'] ?? 1e-9);

            Log::info("LRService: Loaded Ridge JSON model with " . count($this->features) . " features");
        } catch (\Throwable $e) {
            Log::error("LRService: Error loading coeffs: " . $e->getMessage());
        }
    }

    public function predict(array $features): float
    {
        if (empty($this->features)) {
            return 0.0;
        }

        // 1️⃣ Bổ sung ratio nếu chưa có
        if (!isset($features['expense_income_ratio'])) {
            $te = (float)($features['total_expense'] ?? 0);
            $ti = (float)($features['total_income'] ?? 0);
            $features['expense_income_ratio'] = ($ti > 0) ? ($te / $ti) : 0.0;
        }

        // 2️⃣ Log1p cho các cột tiền
        foreach ($this->moneyCols as $col) {
            if (isset($features[$col])) {
                $x = (float)$features[$col];
                $features[$col] = log(1.0 + max(0.0, $x));
            }
        }

        // 3️⃣ Chuẩn hóa (StandardScaler)
        $xStd = [];
        foreach ($this->features as $i => $name) {
            $x = is_numeric($features[$name] ?? null) ? (float)$features[$name] : 0.0;
            $mean  = $this->scalerMean[$i] ?? 0.0;
            $scale = $this->scalerScale[$i] ?? 1.0;
            $xStd[$i] = ($scale != 0.0) ? ($x - $mean) / $scale : 0.0;
        }

        // 4️⃣ Tính y = b + Σ(w_i * x'_i)
        $y = $this->intercept;
        foreach ($this->coefficients as $i => $w) {
            $y += $w * ($xStd[$i] ?? 0.0);
        }

        // 5️⃣ Đảm bảo không âm và hữu hạn
        if (!is_finite($y) || $y < 0) {
            $y = 0.0;
        }

        return round($y, 0);
    }
}
