<?php
use App\Models\PlatformSetting;
it('restricts platform settings to explicitly granted administrators',function(){
 $data=PlatformSetting::defaults();$data['product_name']='NexaiLedger';
 $this->getJson('/api/platform/settings')->assertOk()->assertJsonPath('product_name','Ledger');
 $this->putJson('/api/platform/settings',$data)->assertUnauthorized();
 $this->seed();$owner=demoUser();$this->actingAs($owner);
 $this->putJson('/api/platform/settings',$data)->assertForbidden();
 $owner->forceFill(['is_platform_admin'=>true])->save();
 $this->putJson('/api/platform/settings',$data)->assertOk()->assertJsonPath('product_name','NexaiLedger');
 $data['company_url']='javascript:alert(1)';$data['primary_color']='red;display:none';
 $this->putJson('/api/platform/settings',$data)->assertUnprocessable();
});
it('ignores platform permission supplied by registering users and team managers',function(){
 $this->postJson('/api/auth/register',['business_name'=>'New business','name'=>'New owner','email'=>'new@example.test','password'=>'password123','password_confirmation'=>'password123','is_platform_admin'=>true])->assertCreated()->assertJsonPath('user.is_platform_admin',false);
 $this->seed();$this->actingAs(demoUser());
 $this->postJson('/api/team',['name'=>'Member','email'=>'member@example.test','password'=>'password123','role'=>'admin','is_platform_admin'=>true])->assertCreated()->assertJsonPath('is_platform_admin',false);
});
it('grants and revokes platform access through the server command',function(){
 $this->seed();$this->artisan('platform:admin',['email'=>'owner@brightspark.test'])->assertSuccessful();expect(demoUser()->is_platform_admin)->toBeTrue();
 $this->artisan('platform:admin',['email'=>'owner@brightspark.test','--revoke'=>true])->assertSuccessful();expect(demoUser()->is_platform_admin)->toBeFalse();
});
