<?php
use App\Models\{Client,Invoice,Payment};
beforeEach(function(){$this->seed();});
it('denies staff settings team void and deletion',function(){
    $this->actingAs(demoUser('staff@brightspark.test'));$invoice=Invoice::first();$client=Client::first();$payment=Payment::first();
    $this->getJson('/api/business')->assertForbidden();$this->getJson('/api/team')->assertForbidden();
    $this->postJson('/api/business/logo')->assertForbidden();$this->postJson('/api/team',[])->assertForbidden();
    $this->postJson('/api/invoices/'.$invoice->id.'/void')->assertForbidden();
    $this->deleteJson('/api/clients/'.$client->id)->assertForbidden();$this->deleteJson('/api/payments/'.$payment->id)->assertForbidden();
    $this->getJson('/api/dashboard')->assertOk();$this->postJson('/api/clients',['name'=>'New client'])->assertCreated();
});
it('prevents an admin from removing or demoting an owner',function(){
    $owner=demoUser();$this->actingAs(demoUser('admin@brightspark.test'));
    $this->patchJson('/api/team/'.$owner->id,['role'=>'staff'])->assertForbidden();$this->deleteJson('/api/team/'.$owner->id)->assertForbidden();
});
it('revokes a removed members tokens and preserves audit records',function(){
    $this->actingAs(demoUser());$staff=demoUser('staff@brightspark.test');$staff->createToken('test');
    $this->deleteJson('/api/team/'.$staff->id)->assertNoContent();expect($staff->tokens()->count())->toBe(0);
    $this->getJson('/api/team')->assertJsonCount(2);
});