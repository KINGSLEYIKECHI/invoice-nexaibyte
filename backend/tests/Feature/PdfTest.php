<?php
use App\Models\Invoice;
it('returns a valid nonempty PDF containing the invoice number in its title',function(){
    $this->seed();$this->actingAs(demoUser());$i=Invoice::first();$r=$this->get('/api/invoices/'.$i->id.'/pdf')->assertOk()->assertHeader('Content-Type','application/pdf');
    expect($r->getContent())->toStartWith('%PDF-')->and(strlen($r->getContent()))->toBeGreaterThan(1000);
    expect(str_contains($r->getContent(),mb_convert_encoding($i->number,'UTF-16BE','UTF-8')))->toBeTrue();
});
it('renders light invoice quotation and multipage previews with accurate document data',function(){
 $this->seed();$this->actingAs(demoUser());$b=demoUser()->business;$b->update(['bank_accounts'=>[['bank_name'=>'Example bank','account_name'=>'Business account','account_number'=>'0012345678','show_on_invoice'=>true,'show_on_quotation'=>true]],'payment_instructions'=>'Use the document number as payment reference.']);
 $i=Invoice::first();$html=app(\App\Services\InvoicePdfService::class)->document($i)->output();expect($html)->toStartWith('%PDF-');
 $q=$this->postJson('/api/documents/quotations',invoiceBody(\App\Models\Client::first()->id)+['currency'=>'USD'])->assertCreated()->json();$quote=$this->get('/api/documents/quotations/'.$q['id'].'/pdf')->assertOk()->getContent();
 $longItems=array_map(fn($n)=>['description'=>'Equipment service item '.$n.' with installation and commissioning included','quantity'=>1,'unit_price_kobo'=>125000,'unit'=>'pcs'],range(1,45));$long=$this->postJson('/api/invoices',array_replace(invoiceBody(\App\Models\Client::first()->id),['items'=>$longItems]))->assertCreated()->json();$longPdf=$this->get('/api/invoices/'.$long['id'].'/pdf')->assertOk()->getContent();
 if($dir=getenv('PDF_PREVIEW_DIR')){if(!is_dir($dir))mkdir($dir,0775,true);foreach(['invoice-preview.pdf'=>$html,'quotation-preview.pdf'=>$quote,'invoice-long-preview.pdf'=>$longPdf] as $name=>$bytes)file_put_contents($dir.'/'.$name,$bytes);}
 $view=view('pdf.invoice',['invoice'=>$i->load('business','client','items'),'business'=>$b,'logo'=>null])->render();expect($view)->toContain('0012345678')->not->toContain('display:flex')->not->toContain('display:grid');
});
