<?php
namespace App\Jobs;
use App\Models\{PaymentNotification,Payment};
use App\Services\IntegrationSettings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\{DB,Mail};
class SendPaymentEmail implements ShouldQueue {
 use Queueable;
 public int $tries=3;public int $timeout=60;
 public function __construct(public int $notificationId,public int $businessId){$this->afterCommit();}
 public function backoff():array{return [30,120,600];}
 public function handle():void {
  app(IntegrationSettings::class)->apply();
  $notice=PaymentNotification::where('business_id',$this->businessId)->find($this->notificationId);if(!$notice||in_array($notice->status,['sent','logged']))return;
  try {DB::transaction(function(){
   $n=PaymentNotification::where('business_id',$this->businessId)->lockForUpdate()->find($this->notificationId);
   if(!$n||in_array($n->status,['sent','logged'])||!Payment::withoutGlobalScopes()->where('business_id',$this->businessId)->whereKey($n->payment_id)->exists())return;
   $n->increment('attempts');$d=$n->details;$money=fn($k)=>'NGN '.number_format($k/100,2);
   $body=($n->kind==='owner'?'A payment has been recorded for your business.':'Thank you. Your payment has been recorded.')."\n\nBusiness: {$d['business_name']}\nInvoice: {$d['invoice_number']}\nPayment: ".$money($d['amount_kobo'])."\nRemaining balance at recording: ".$money($d['balance_kobo'])."\nPayment date: {$d['paid_on']}\n\nThis confirms a payment recorded in the invoicing app.";
   Mail::raw($body,fn($m)=>$m->to($n->recipient)->subject('Payment recorded: '.$d['invoice_number']));
   $n->update(['status'=>config('mail.default')==='log'?'logged':'sent','sent_at'=>now(),'error'=>null]);
  });}catch(\Throwable $e){$notice->increment('attempts');$notice->update(['error'=>'Email delivery failed. Check SMTP settings.']);throw new \RuntimeException('Payment email delivery failed. Check SMTP settings.');}
 }
 public function failed(?\Throwable $e):void {PaymentNotification::where('business_id',$this->businessId)->whereKey($this->notificationId)->whereNotIn('status',['sent','logged'])->update(['status'=>'failed','error'=>'Email delivery failed after retries.']);}
}
