<?php
use App\Models\Client;
beforeEach(function(){$this->seed();$this->actingAs(demoUser());});
it('creates edits searches and soft deletes clients and normalizes Nigerian phones',function(){
    $r=$this->postJson('/api/clients',['name'=>'New Client','phone'=>'08012345678','business_id'=>999])->assertCreated()->assertJsonPath('phone','+2348012345678')->assertJsonPath('business_id',demoUser()->business_id);
    $id=$r->json('id');$this->putJson('/api/clients/'.$id,['name'=>'Updated Client'])->assertOk();
    $this->getJson('/api/clients?search=Updated')->assertJsonPath('total',1);
    $this->deleteJson('/api/clients/'.$id)->assertNoContent();$this->getJson('/api/clients/'.$id)->assertNotFound();$this->assertSoftDeleted('clients',['id'=>$id]);
});
it('rejects invalid phones',fn()=>$this->postJson('/api/clients',['name'=>'Bad phone','phone'=>'abc'])->assertUnprocessable());