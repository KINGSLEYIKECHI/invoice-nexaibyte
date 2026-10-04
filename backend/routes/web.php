<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicInvoiceController;
Route::get('/',fn()=>response()->json(['application'=>'Invoice SaaS','health'=>'/up']));
Route::get('/i/{public_token}',[PublicInvoiceController::class,'show'])->name('public.invoice')->middleware('signed');
Route::get('/i/{public_token}/pdf',[PublicInvoiceController::class,'pdf'])->name('public.invoice.pdf')->middleware('signed');
Route::get('/media/businesses/{business}/logo',[\App\Http\Controllers\PublicMediaController::class,'business'])->whereNumber('business');
Route::get('/media/platform/{kind}',[\App\Http\Controllers\PublicMediaController::class,'platform'])->whereIn('kind',['logo','favicon']);

Route::get('/ads.txt',[\App\Http\Controllers\AdvertisingController::class,'adsTxt']);
