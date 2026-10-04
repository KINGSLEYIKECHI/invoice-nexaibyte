<?php
namespace App\Http\Controllers;
use App\Models\{Invoice,Client,Payment};
class DashboardController extends Controller {
    public function __invoke() {
        $issued=Invoice::whereNotIn('status',['draft','void']);
        return ['billed_kobo'=>(int)(clone $issued)->sum('total_kobo'),'paid_kobo'=>(int)(clone $issued)->sum('amount_paid_kobo'),
        'outstanding_kobo'=>(int)(clone $issued)->selectRaw('COALESCE(SUM(total_kobo-amount_paid_kobo),0) as total')->value('total'),
        'overdue_kobo'=>(int)Invoice::where('status','overdue')->selectRaw('COALESCE(SUM(total_kobo-amount_paid_kobo),0) as total')->value('total'),
        'clients_count'=>Client::count(),'invoices_count'=>Invoice::count(),'recent_invoices'=>Invoice::with('client')->latest('id')->limit(6)->get(),
        'recent_payments'=>Payment::with('invoice')->latest('id')->limit(5)->get(),'status_counts'=>Invoice::selectRaw('status,COUNT(*) as count')->groupBy('status')->pluck('count','status')];
    }
}