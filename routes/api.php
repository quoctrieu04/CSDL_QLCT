<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChiTieuController;
use App\Http\Controllers\Api\DanhMucController;
// use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\PredictionController;
use App\Http\Controllers\Api\AlertIngestController;
use App\Http\Controllers\Api\AlertListController;
use App\Http\Controllers\Api\InCategoryController;
use App\Http\Controllers\Api\BankAccountController;
use App\Http\Controllers\Api\OutInvoiceController;
use App\Http\Controllers\Api\InInvoiceController;
use App\Http\Controllers\Api\BudgetAllocationController;
use App\Http\Controllers\Api\InCategoryBalanceController;
use App\Http\Controllers\Api\SavingController;
use App\Http\Controllers\Api\SavingTransactionController;
use App\Http\Controllers\Api\InvestmentController;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('login',    [AuthController::class, 'login'])->name('auth.login');
});


Route::middleware('auth:api')->group(function () {

    // Current user
    Route::get('auth/user',             [AuthController::class, 'me'])->name('auth.me');
    Route::put('auth/user',             [AuthController::class, 'update'])->name('auth.update');
    Route::post('auth/change-password', [AuthController::class, 'changePassword'])->name('auth.change-password');
    Route::post('auth/logout',          [AuthController::class, 'logout'])->name('auth.logout');

    Route::apiResource('in-categories', InCategoryController::class);

    Route::get('bankaccounts', [BankAccountController::class, 'index']);
    Route::post('bankaccounts', [BankAccountController::class, 'store']);
    Route::put('bankaccounts/{id}', [BankAccountController::class, 'update']);
    Route::delete('bankaccounts/{id}', [BankAccountController::class, 'destroy']);
    Route::post('bankaccounts/{id}/default', [BankAccountController::class, 'makeDefault']);
    // ========== Giao dịch thu / chi ==========
    Route::get('in-invoices',  [InInvoiceController::class, 'index'])->name('invoices.in.index');
    Route::post('in-invoices', [InInvoiceController::class, 'store'])->name('invoices.in.store');

    Route::get('out-invoices',  [OutInvoiceController::class, 'index'])->name('invoices.out.index');
    Route::post('out-invoices', [OutInvoiceController::class, 'store'])->name('invoices.out.store');



    Route::apiResource('categories', DanhMucController::class)
        ->parameters(['categories' => 'danh_muc']);
    // Route::get('/in-category-balances', [InCategoryBalanceController::class, 'index']);
    // Route::post('/in-category-balances', [InCategoryBalanceController::class, 'store']);
    // ========== Phân chia ngân sách ==========
    Route::get('budgets/summary', [BudgetAllocationController::class, 'summary']);
    Route::post('budgets/allocate', [BudgetAllocationController::class, 'allocate']);

    
  
    Route::post('transactions', [TransactionController::class, 'store']);
    Route::get('transactions/spent-by-category', [TransactionController::class, 'spentByCategory']);

    Route::get('predict',        [PredictionController::class, 'predictMonthly'])->name('predict.monthly');
    Route::post('alerts/ingest', [AlertIngestController::class, 'store'])->name('alerts.ingest');

    Route::apiResource('saving', SavingController::class);
    Route::get('saving/{id}/transactions', [SavingTransactionController::class, 'index']);
    Route::post('saving_transaction', [SavingTransactionController::class, 'store']);

   // ========== INVESTMENT ==========
Route::get('investments',        [InvestmentController::class, 'index']);
Route::post('investments',       [InvestmentController::class, 'store']);
Route::put('investments/{id}',   [InvestmentController::class, 'update']);
Route::delete('investments/{id}',[InvestmentController::class, 'destroy']);

// ========== INVESTMENT TRANSACTIONS ==========
Route::get(
    'investments/{id}/transactions',
    [InvestmentTransactionController::class, 'index']
);

Route::post(
    'investments/{id}/deposit',
    [InvestmentTransactionController::class, 'deposit']
);

Route::post(
    'investments/{id}/withdraw',
    [InvestmentTransactionController::class, 'withdraw']
);


});

Route::get('alerts', [AlertListController::class, 'index'])->name('alerts.index');
