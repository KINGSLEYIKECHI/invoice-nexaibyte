<?php
namespace App\Jobs;
use App\Models\{Invoice,MessageLog};
use App\Services\{InvoicePdfService,WhatsAppService};
use App\Mail\InvoiceMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\{DB,Mail};
use Throwable;
abstract class SendInvoiceMessage implements ShouldQueue {
    use Queueable;
    public int $tries=3;
    public int $timeout=90;
    public function __construct(public int $logId,public int $businessId) { $this->afterCommit(); }
    public function backoff(): array { return [30,120,600]; }
    public function handle(InvoicePdfService $pdf,WhatsAppService $whatsapp): void {
        app(\App\Services\IntegrationSettings::class)->apply();
        $log=MessageLog::withoutGlobalScopes()->where('business_id',$this->businessId)->findOrFail($this->logId);
        if($log->status==='sent') return;
        $invoice=Invoice::withoutGlobalScopes()->where('business_id',$this->businessId)->findOrFail($log->invoice_id);
        if($invoice->status==='void') { $log->update(['status'=>'failed','error'=>'Invoice was voided before delivery.']); return; }
        $log->increment('attempts');
        try {
            $pdf->hydrate($invoice); $provider=null;
            if($log->channel==='email') Mail::to($log->recipient)->send(new InvoiceMail($invoice,$pdf->bytes($invoice),$log->message));
            else $provider=$whatsapp->send($invoice,$log);
            DB::transaction(function() use($invoice,$log,$provider) {
                $current=Invoice::withoutGlobalScopes()->where('business_id',$this->businessId)->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
                $log->update(['status'=>'sent','provider_message_id'=>$provider,'error'=>null]);
                if($current->status!=='void') { $current->sent_at??=now(); $current->recalculateStatus(); }
            },5);
        } catch(Throwable $e) { $log->update(['error'=>'Delivery failed. Check provider settings and retry.']); throw new \RuntimeException('Invoice delivery failed. Check provider settings.'); }
    }
    public function failed(?Throwable $e): void {
        MessageLog::withoutGlobalScopes()->where('business_id',$this->businessId)->whereKey($this->logId)->update(['status'=>'failed','error'=>'Delivery failed. Check provider settings and retry.']);
    }
}