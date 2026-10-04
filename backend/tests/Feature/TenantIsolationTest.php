<?php
use App\Models\{Client,Invoice,Payment};
beforeEach(function(){ $this->seed();$this->actingAs(demoUser()); });
it('returns 404 for another tenants client reads writes and deletes',function(){
    $client=Client::withoutGlobalScopes()->where('business_id',demoUser('owner@kemtech.test')->business_id)->first();
    $this->getJson('/api/clients/'.$client->id)->assertNotFound();
    $this->putJson('/api/clients/'.$client->id,['name'=>'Intrusion'])->assertNotFound();
    $this->deleteJson('/api/clients/'.$client->id)->assertNotFound();
});
it('isolates invoice reads writes delivery pdf duplicate void and payments',function(){
    $invoice=Invoice::withoutGlobalScopes()->where('business_id',demoUser('owner@kemtech.test')->business_id)->first();
    $this->getJson('/api/invoices/'.$invoice->id)->assertNotFound();
    $this->putJson('/api/invoices/'.$invoice->id,invoiceBody($invoice->client_id))->assertNotFound();
    foreach(['void','duplicate','send','payments'] as $action)$this->postJson('/api/invoices/'.$invoice->id.'/'.$action,[])->assertNotFound();
    foreach(['pdf','messages'] as $action)$this->getJson('/api/invoices/'.$invoice->id.'/'.$action)->assertNotFound();
    $p=Payment::withoutGlobalScopes()->create(['business_id'=>$invoice->business_id,'invoice_id'=>$invoice->id,'amount_kobo'=>1,'method'=>'cash','paid_on'=>today(),'recorded_by'=>demoUser('owner@kemtech.test')->id]);
    $this->deleteJson('/api/payments/'.$p->id)->assertNotFound();
});
it('never lists another tenants rows or accepts their foreign keys',function(){
    $b=demoUser()->business_id;
    foreach(['/api/clients','/api/invoices'] as $route) foreach($this->getJson($route)->assertOk()->json('data') as $row) expect($row['business_id'])->toBe($b);
    $other=Client::withoutGlobalScopes()->where('business_id',demoUser('owner@kemtech.test')->business_id)->first();
    $this->postJson('/api/invoices',invoiceBody($other->id))->assertUnprocessable();
    expect($this->getJson('/api/team')->json())->toHaveCount(3);
});
it('fails closed without a tenant context',function(){auth()->logout();expect(Client::count())->toBe(0);expect(Invoice::count())->toBe(0);});