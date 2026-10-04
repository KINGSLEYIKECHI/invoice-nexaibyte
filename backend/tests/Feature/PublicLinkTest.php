<?php
use App\Models\Invoice;
use Illuminate\Support\Facades\URL;
it('shows only the requested invoice without auth and provides a signed pdf',function(){
    $this->seed();$i=Invoice::withoutGlobalScopes()->first();
    $this->get($i->public_url)->assertOk()->assertSee($i->number)->assertDontSee('KemTech');
    $this->get(URL::signedRoute('public.invoice.pdf',['public_token'=>$i->public_token]))->assertOk()->assertHeader('Content-Type','application/pdf');
    $this->get(URL::signedRoute('public.invoice',['public_token'=>'wrong']))->assertNotFound();
    $this->get('/i/'.$i->public_token)->assertForbidden();
});