<?php
use App\Models\Invoice;
it('returns a valid nonempty PDF containing the invoice number in its title',function(){
    $this->seed();$this->actingAs(demoUser());$i=Invoice::first();$r=$this->get('/api/invoices/'.$i->id.'/pdf')->assertOk()->assertHeader('Content-Type','application/pdf');
    expect($r->getContent())->toStartWith('%PDF-')->and(strlen($r->getContent()))->toBeGreaterThan(1000);
    expect(str_contains($r->getContent(),mb_convert_encoding($i->number,'UTF-16BE','UTF-8')))->toBeTrue();
});