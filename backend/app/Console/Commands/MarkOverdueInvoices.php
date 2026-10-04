<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Models\{Invoice,Client,MessageLog};
use App\Jobs\SendOverdueReminder;
use Illuminate\Support\Facades\DB;
class MarkOverdueInvoices extends Command {
    protected $signature='invoices:overdue';
    protected $description='Mark overdue balances and queue each due-date reminder once.';
    public function handle(): int {
        Invoice::withoutGlobalScopes()->whereNotIn('status',['draft','void','paid'])->whereColumn('amount_paid_kobo','<','total_kobo')->chunkById(100,function($invoices) {
            foreach($invoices as $invoice) DB::transaction(function() use($invoice) {
                $invoice=Invoice::withoutGlobalScopes()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
                $invoice->recalculateStatus();
                $kind=$invoice->due_date->isSameDay(today()->addDays(config('invoice.reminder_days')))?'before':($invoice->due_date->isSameDay(today()->subDay())?'overdue':null);
                if(!$kind || $invoice->balance_kobo===0) return;
                $client=Client::withoutGlobalScopes()->withTrashed()->where('business_id',$invoice->business_id)->find($invoice->client_id);
                foreach(['email'=>$client?->email,'whatsapp'=>$client?->phone] as $channel=>$recipient) {
                    if(!$recipient) continue;
                    $key=$invoice->id.':'.$invoice->due_date->format('Y-m-d').':'.$kind.':'.$channel;
                    $log=MessageLog::withoutGlobalScopes()->firstOrCreate(['reminder_key'=>$key],['business_id'=>$invoice->business_id,'invoice_id'=>$invoice->id,'channel'=>$channel,'recipient'=>$recipient,'message'=>'Payment reminder for invoice '.$invoice->number,'status'=>'queued']);
                    if($log->wasRecentlyCreated) SendOverdueReminder::dispatch($log->id,$invoice->business_id);
                }
            },5);
        });
        $this->info('Overdue statuses and reminders updated.'); return self::SUCCESS;
    }
}