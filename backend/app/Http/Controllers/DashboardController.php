<?php
namespace App\Http\Controllers;
use App\Models\{Invoice,Client,Payment};
class DashboardController extends Controller {
    public function __invoke() {
        $currency=auth()->user()->business->currency;$groups=Invoice::whereNotIn('status',['draft','void'])->selectRaw('currency, currency_minor_units, SUM(total_kobo) AS billed_kobo, SUM(amount_paid_kobo) AS paid_kobo, SUM(total_kobo-amount_paid_kobo) AS outstanding_kobo')->groupBy('currency','currency_minor_units')->toBase()->get()->map(fn($x)=>['currency'=>$x->currency,'currency_minor_units'=>(int)$x->currency_minor_units,'billed_kobo'=>(int)$x->billed_kobo,'paid_kobo'=>(int)$x->paid_kobo,'outstanding_kobo'=>(int)$x->outstanding_kobo]);
        $issued=Invoice::where('currency',$currency)->whereNotIn('status',['draft','void']);
        return ['currency'=>$currency,'currency_minor_units'=>app(\App\Services\CurrencyService::class)->precision($currency),'currency_totals'=>$groups,'billed_kobo'=>(int)(clone $issued)->sum('total_kobo'),'paid_kobo'=>(int)(clone $issued)->sum('amount_paid_kobo'),
        'outstanding_kobo'=>(int)(clone $issued)->selectRaw('COALESCE(SUM(total_kobo-amount_paid_kobo),0) as total')->value('total'),
        'overdue_kobo'=>(int)Invoice::where('currency',$currency)->where('status','overdue')->selectRaw('COALESCE(SUM(total_kobo-amount_paid_kobo),0) as total')->value('total'),
        'clients_count'=>Client::count(),'invoices_count'=>Invoice::count(),'recent_invoices'=>Invoice::with('client')->latest('id')->limit(6)->get(),
        'recent_payments'=>Payment::with('invoice')->latest('id')->limit(5)->get(),'status_counts'=>Invoice::selectRaw('status,COUNT(*) as count')->groupBy('status')->pluck('count','status')];
    }
}