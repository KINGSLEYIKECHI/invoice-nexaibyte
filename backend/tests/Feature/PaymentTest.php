<?php
use App\Models\{Invoice,Payment};
beforeEach(function(){$this->seed();$this->actingAs(demoUser());});
it('records partial and full payments rejects overpayment and recalculates on removal',function(){
    $i=Invoice::where('status','sent')->first();$url='/api/invoices/'.$i->id.'/payments';$p=['method'=>'bank_transfer','paid_on'=>today()->format('Y-m-d'),'reference'=>'TEST'];
    $this->postJson($url,$p+['amount_kobo'=>100000])->assertCreated()->assertJsonPath('status','partially_paid');
    $this->postJson($url,$p+['amount_kobo'=>$i->total_kobo])->assertUnprocessable();
    $this->postJson($url,$p+['amount_kobo'=>$i->total_kobo-100000])->assertCreated()->assertJsonPath('status','paid')->assertJsonPath('balance_kobo',0);
    $payment=Payment::where('invoice_id',$i->id)->latest('id')->first();
    $this->deleteJson('/api/payments/'.$payment->id)->assertOk()->assertJsonPath('status','partially_paid');
    $payment=Payment::where('invoice_id',$i->id)->first();$this->deleteJson('/api/payments/'.$payment->id)->assertJsonPath('status','sent');
});
it('rejects payments against drafts voids and future dates',function(){
    $draft=Invoice::where('status','draft')->first();$data=['amount_kobo'=>1,'method'=>'cash','paid_on'=>today()->format('Y-m-d')];
    $this->postJson('/api/invoices/'.$draft->id.'/payments',$data)->assertUnprocessable();
    $sent=Invoice::where('status','sent')->first();$this->postJson('/api/invoices/'.$sent->id.'/payments',array_replace($data,['paid_on'=>today()->addDay()->format('Y-m-d')]))->assertUnprocessable();
    $this->postJson('/api/invoices/'.$sent->id.'/void')->assertOk();$this->postJson('/api/invoices/'.$sent->id.'/payments',$data)->assertUnprocessable();
});