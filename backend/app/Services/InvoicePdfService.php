<?php
namespace App\Services;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
class InvoicePdfService {
    public function hydrate(Invoice $invoice): Invoice {
        $invoice->load('business');
        $invoice->setRelation('client',\App\Models\Client::withoutGlobalScopes()->withTrashed()->where('business_id',$invoice->business_id)->findOrFail($invoice->client_id));
        $invoice->setRelation('items',$invoice->items()->withoutGlobalScopes()->where('business_id',$invoice->business_id)->get());
        return $invoice;
    }
    public function document(Invoice $invoice) {
        $invoice=$this->hydrate($invoice); $logo=app(MediaStorage::class)->dataUri($invoice->business->logoAsset());
        return Pdf::loadView('pdf.invoice',['invoice'=>$invoice,'business'=>$invoice->business,'logo'=>$logo])->setPaper('a4')->setOption('isRemoteEnabled',false);
    }
    public function bytes(Invoice $invoice): string { return $this->document($invoice)->output(); }
    public function download(Invoice $invoice) { return $this->document($invoice)->download($invoice->number.'.pdf'); }
}