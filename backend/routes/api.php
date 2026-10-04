<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{AuthController,ClientController,InvoiceController,InvoiceSendController,PaymentController,TeamController,DashboardController,BusinessSettingsController};
Route::middleware('throttle:10,1')->group(function() {
    Route::post('auth/register',[AuthController::class,'register']);
    Route::post('auth/login',[AuthController::class,'login']);
});
Route::middleware(['auth:sanctum','tenant'])->group(function() {
    Route::get('me',[AuthController::class,'me']);
    Route::post('auth/logout',[AuthController::class,'logout']);
    Route::get('dashboard',DashboardController::class);
    Route::apiResource('clients',ClientController::class);
    Route::apiResource('invoices',InvoiceController::class)->except('destroy');
    Route::post('invoices/{invoice}/void',[InvoiceController::class,'void']);
    Route::post('invoices/{invoice}/duplicate',[InvoiceController::class,'duplicate']);
    Route::get('invoices/{invoice}/pdf',[InvoiceController::class,'pdf']);
    Route::post('invoices/{invoice}/send',[InvoiceSendController::class,'store']);
    Route::get('invoices/{invoice}/messages',[InvoiceSendController::class,'index']);
    Route::post('invoices/{invoice}/payments',[PaymentController::class,'store']);
    Route::delete('payments/{payment}',[PaymentController::class,'destroy']);
    Route::middleware('role:owner,admin')->group(function() {
        Route::get('business',[BusinessSettingsController::class,'show']);
        Route::put('business',[BusinessSettingsController::class,'update']);
        Route::delete('business',[BusinessSettingsController::class,'destroy'])->middleware('role:owner');
        Route::post('business/logo',[BusinessSettingsController::class,'logo'])->middleware('throttle:10,1');
        Route::get('team',[TeamController::class,'index']);
        Route::post('team',[TeamController::class,'store']);
        Route::patch('team/{user}',[TeamController::class,'update']);
        Route::delete('team/{user}',[TeamController::class,'destroy']);
    });
});
Route::get('platform/settings',[\App\Http\Controllers\PlatformSettingsController::class,'show']);
Route::put('platform/settings',[\App\Http\Controllers\PlatformSettingsController::class,'update'])->middleware('auth:sanctum');

Route::post('platform/images/{kind}',[\App\Http\Controllers\PlatformSettingsController::class,'upload'])->middleware(['auth:sanctum','throttle:10,1'])->whereIn('kind',['logo','favicon']);
