<?php
namespace App\Http\Controllers;
use App\Models\Invoice;
use App\Http\Requests\StoreInvoiceRequest;
use App\Services\{InvoiceTotalsService,InvoiceNumberService,InvoicePdfService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class InvoiceController extends Controller {
    public function index(Request $r) {
        $q=Invoice::with('client');
        foreach(['status','client_id'] as $key) if($r->filled($key)) $q->where($key,$r->query($key));
        if($r->filled('from')) $q->whereDate('issue_date','>=',$r->query('from'));
        if($r->filled('to')) $q->whereDate('issue_date','<=',$r->query('to'));
        if($s=$r->query('search')) $q->where('number','like',"%$s%");
        return $q->latest('id')->paginate(min(max((int)$r->query('per_page',20),1),100));
    }
    public function show(Invoice $invoice) { return $invoice->load(['client','items','payments','messages','business']); }
    public function store(StoreInvoiceRequest $r,InvoiceTotalsService $totals,InvoiceNumberService $numbers) {
        return DB::transaction(function() use($r,$totals,$numbers) {
            $data=$r->validated(); $result=$totals->calculate($data['items'],$data['discount_kobo']??0,$data['tax_percent']);
            unset($data['items']); $items=$result['items']; unset($result['items']);
            $invoice=Invoice::create(array_merge($data,$result,['business_id'=>$r->user()->business_id,'created_by'=>$r->user()->id,'number'=>$numbers->next($r->user()->business_id),'public_token'=>(string)Str::uuid(),'status'=>'draft']));
            $invoice->items()->createMany($items);
            return response()->json($invoice->load(['client','items']),201);
        },5);
    }
    public function update(StoreInvoiceRequest $r,Invoice $invoice,InvoiceTotalsService $totals) {
        return DB::transaction(function() use($r,$invoice,$totals) {
            $invoice=Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            abort_unless($invoice->status==='draft' && !$invoice->messages()->where('status','queued')->exists(),422,'Only drafts without pending delivery can be edited.');
            $data=$r->validated(); $result=$totals->calculate($data['items'],$data['discount_kobo']??0,$data['tax_percent']);
            unset($data['items']); $items=$result['items']; unset($result['items']);
            $invoice->update(array_merge($data,$result)); $invoice->items()->delete(); $invoice->items()->createMany($items);
            return $invoice->load(['client','items']);
        },5);
    }
    public function void(Invoice $invoice) {
        $this->authorize('void',$invoice);
        return DB::transaction(function() use($invoice) {
            $invoice=Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            abort_if($invoice->amount_paid_kobo>0 || $invoice->status==='paid',422,'Remove payments before voiding this invoice.');
            $invoice->update(['status'=>'void']); return $invoice;
        });
    }
    public function duplicate(Invoice $invoice,InvoiceNumberService $numbers) {
        return DB::transaction(function() use($invoice,$numbers) {
            $copy=$invoice->replicate(['number','public_token','sent_at','paid_at','created_at','updated_at']);
            $copy->number=$numbers->next($invoice->business_id); $copy->public_token=(string)Str::uuid(); $copy->status='draft'; $copy->amount_paid_kobo=0; $copy->created_by=auth()->id(); $copy->issue_date=today(); $copy->due_date=today()->addDays(14); $copy->save();
            foreach($invoice->items as $item) $copy->items()->create($item->only(['description','quantity','unit_price_kobo','line_total_kobo','position']));
            return response()->json($copy->load(['client','items']),201);
        },5);
    }
    public function pdf(Invoice $invoice,InvoicePdfService $pdf) { return $pdf->download($invoice); }
}