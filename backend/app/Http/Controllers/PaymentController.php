<?php
namespace App\Http\Controllers;
use App\Models\{Invoice,Payment};
use App\Http\Requests\StorePaymentRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class PaymentController extends Controller {
    public function store(StorePaymentRequest $r,Invoice $invoice) {
        return DB::transaction(function() use($r,$invoice) {
            $invoice=Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            abort_if(in_array($invoice->status,['draft','void','paid']),422,'Payments require an issued invoice with a balance.');
            $invoice->recalculateStatus();
            if($r->amount_kobo>$invoice->balance_kobo) throw ValidationException::withMessages(['amount_kobo'=>'Payment exceeds the outstanding balance.']);
            $payment=$invoice->payments()->create(array_merge($r->validated(),['recorded_by'=>$r->user()->id]));
            $invoice->recalculateStatus();
            app(\App\Services\PaymentEmails::class)->queue($invoice,$payment);
            return response()->json($invoice->load(['client','items','payments','messages','business']),201);
        },5);
    }
    public function destroy(Payment $payment) {
        abort_unless(auth()->user()->isManager(),403);
        return DB::transaction(function() use($payment) {
            $invoice=Invoice::whereKey($payment->invoice_id)->lockForUpdate()->firstOrFail();
            $payment->delete(); $invoice->recalculateStatus(); return $invoice->load(['client','items','payments','messages','business']);
        },5);
    }
}