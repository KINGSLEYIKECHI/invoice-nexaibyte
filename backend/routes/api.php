<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{AuthController,ClientController,InvoiceController,InvoiceSendController,PaymentController,TeamController,DashboardController,BusinessSettingsController};
Route::middleware('throttle:10,1')->group(function() {
    Route::post('auth/register',[AuthController::class,'register']);
    Route::post('auth/login',[AuthController::class,'login']);
});
Route::middleware(['auth:sanctum','tenant','activity'])->group(function() {
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
Route::middleware(['auth:sanctum','platform.admin','activity'])->prefix('platform')->group(function(){
 $c=\App\Http\Controllers\PlatformOperationsController::class;
 Route::get('integrations',[$c,'integrations']);Route::put('integrations',[$c,'saveIntegrations']);
 Route::post('integrations/test/{kind}',[$c,'testIntegration'])->whereIn('kind',['smtp','cloudinary','whatsapp'])->middleware('throttle:5,1');
 Route::get('overview',[$c,'overview']);Route::get('users',[$c,'users']);Route::get('activity',[$c,'activity']);Route::get('payment-emails',[$c,'deliveries']);Route::post('payment-emails/{id}/retry',[$c,'retryDelivery'])->whereNumber('id')->middleware('throttle:5,1');
});
Route::get('advertising',[\App\Http\Controllers\AdvertisingController::class,'show']);
Route::put('platform/advertising',[\App\Http\Controllers\AdvertisingController::class,'update'])->middleware(['auth:sanctum','platform.admin','activity']);
Route::get('currencies',fn()=>app(\App\Services\CurrencyService::class)->all());
Route::middleware(['auth:sanctum','tenant','activity'])->group(function(){
 $c=\App\Http\Controllers\CommercialDocumentController::class;
 Route::post('invoices/{invoice}/delivery-note',[$c,'fromInvoice']);
 Route::prefix('documents/{kind}')->where(['kind'=>'quotations|delivery-notes'])->group(function()use($c){
  Route::get('/',[$c,'index']);Route::post('/',[$c,'store']);Route::get('{document}',[$c,'show']);Route::put('{document}',[$c,'update']);Route::post('{document}/transition',[$c,'transition']);Route::post('{document}/convert',[$c,'convert']);Route::get('{document}/pdf',[$c,'pdf']);
 });
});
Route::middleware(['auth:sanctum','tenant','activity'])->group(function(){
 $c=\App\Http\Controllers\ProductController::class;
 Route::get('products/template/{format}',[$c,'template'])->whereIn('format',['csv','xlsx']);
 Route::get('products',[$c,'index']);Route::get('products/{product}',[$c,'show'])->whereNumber('product');
 Route::middleware('role:owner,admin')->group(function()use($c){Route::post('products',[$c,'store']);Route::put('products/{product}',[$c,'update'])->whereNumber('product');Route::post('products/import',[$c,'import'])->middleware('throttle:10,1');});
});
