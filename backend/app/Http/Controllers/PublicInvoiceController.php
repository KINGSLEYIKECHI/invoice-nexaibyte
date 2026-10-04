<?php
namespace App\Http\Controllers;
use App\Models\Invoice;
use App\Services\InvoicePdfService;
class PublicInvoiceController extends Controller {
    private function invoice(string $token,InvoicePdfService $pdf): Invoice { return $pdf->hydrate(Invoice::withoutGlobalScopes()->where('public_token',$token)->firstOrFail()); }
    public function show(string $public_token,InvoicePdfService $pdf) {
        $invoice=$this->invoice($public_token,$pdf);
        return view('public.invoice',['invoice'=>$invoice,'business'=>$invoice->business,'logo'=>$invoice->business->logo_url]);
    }
    public function pdf(string $public_token,InvoicePdfService $pdf) { return $pdf->download($this->invoice($public_token,$pdf)); }
}