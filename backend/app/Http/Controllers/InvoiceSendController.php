<?php
namespace App\Http\Controllers;
use App\Models\Invoice;
use App\Http\Requests\SendInvoiceRequest;
use App\Jobs\{SendInvoiceEmail,SendInvoiceWhatsApp};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class InvoiceSendController extends Controller {
    public function store(SendInvoiceRequest $r,Invoice $invoice) {
        return DB::transaction(function() use($r,$invoice) {
            $invoice=Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            abort_if($invoice->status==='void',422,'Void invoices cannot be sent.');
            foreach($r->validated('channels') as $channel) {
                $recipient=$channel==='email'?$invoice->client->email:$invoice->client->phone;
                if(!$recipient) throw ValidationException::withMessages(['channels'=>"Client has no $channel contact."]);
            }
            $logs=[];
            foreach($r->validated('channels') as $channel) {
                $recipient=$channel==='email'?$invoice->client->email:$invoice->client->phone;
                if(!$recipient) throw ValidationException::withMessages(['channels'=>"Client has no $channel contact."]);
                $log=$invoice->messages()->create(['channel'=>$channel,'recipient'=>$recipient,'message'=>$r->validated('message'),'status'=>'queued']);
                $job=$channel==='email'?SendInvoiceEmail::class:SendInvoiceWhatsApp::class;
                $job::dispatch($log->id,$invoice->business_id); $logs[]=$log;
            }
            return response()->json(['message'=>'Delivery queued.','data'=>$logs],202);
        },5);
    }
    public function index(Invoice $invoice) { return $invoice->messages()->latest()->get(); }
}