<?php
namespace App\Services;
use App\Models\{Invoice,Payment,PaymentNotification};
use App\Jobs\SendPaymentEmail;
class PaymentEmails {
 public function queue(Invoice $invoice,Payment $payment):void {
  $v=app(IntegrationSettings::class)->values();$invoice->load(['business','client']);$recipients=[];
  if($v['payment_owner_email'])foreach($invoice->business->users()->where('role','owner')->pluck('email') as $email)$recipients[$email]='owner';
  if($v['payment_client_email']&&$invoice->client?->email)$recipients[$invoice->client->email]=$recipients[$invoice->client->email]??'client';
  foreach($recipients as $recipient=>$kind){
   if(!filter_var($recipient,FILTER_VALIDATE_EMAIL))continue;
   $notice=PaymentNotification::firstOrCreate(['payment_id'=>$payment->id,'recipient'=>$recipient],['business_id'=>$invoice->business_id,'kind'=>$kind,'details'=>['invoice_number'=>$invoice->number,'business_name'=>$invoice->business->name,'amount_kobo'=>$payment->amount_kobo,'balance_kobo'=>$invoice->balance_kobo,'paid_on'=>$payment->paid_on->format('Y-m-d')]]);
   if($notice->wasRecentlyCreated)SendPaymentEmail::dispatch($notice->id,$invoice->business_id)->afterCommit();
  }
 }
}
