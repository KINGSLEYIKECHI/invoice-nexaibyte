<?php
use App\Models\{Client,Invoice};
beforeEach(function(){$this->seed();$this->actingAs(demoUser());});
it('computes totals ignores supplied totals and generates a new number',function(){
    $body=invoiceBody(Client::first()->id)+['total_kobo'=>1,'status'=>'paid','number'=>'FAKE'];
    $r=$this->postJson('/api/invoices',$body)->assertCreated()->assertJsonPath('total_kobo',107500)->assertJsonPath('status','draft')->assertJsonPath('number','INV-0006');
    $this->postJson('/api/invoices',$body)->assertJsonPath('number','INV-0007');
    $this->actingAs(demoUser('owner@kemtech.test'));$this->postJson('/api/invoices',invoiceBody(Client::first()->id))->assertJsonPath('number','KT-0002');
});
it('only edits drafts and duplicates into a clean draft',function(){
    $sent=Invoice::where('status','sent')->first();$this->putJson('/api/invoices/'.$sent->id,invoiceBody($sent->client_id))->assertUnprocessable();
    $draft=Invoice::where('status','draft')->first();$this->putJson('/api/invoices/'.$draft->id,invoiceBody($draft->client_id))->assertOk()->assertJsonPath('total_kobo',107500);
    $this->postJson('/api/invoices/'.$sent->id.'/duplicate')->assertCreated()->assertJsonPath('status','draft')->assertJsonPath('amount_paid_kobo',0);
});
it('voids only unpaid invoices and disallows further edits',function(){
    $sent=Invoice::where('status','sent')->first();$this->postJson('/api/invoices/'.$sent->id.'/void')->assertOk()->assertJsonPath('status','void');
    $this->postJson('/api/invoices/'.$sent->id.'/send',['channels'=>['email']])->assertUnprocessable();
    $paid=Invoice::where('status','paid')->first();$this->postJson('/api/invoices/'.$paid->id.'/void')->assertUnprocessable();
});
it('rejects discounts above subtotal and nested field injection',function(){
    $b=invoiceBody(Client::first()->id);$b['discount_kobo']=100001;$this->postJson('/api/invoices',$b)->assertUnprocessable();
    $b['discount_kobo']=0;$b['items'][0]['id']=1;$this->postJson('/api/invoices',$b)->assertUnprocessable();
});