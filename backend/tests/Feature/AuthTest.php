<?php
use App\Models\{Business,User};
it('registers a business and owner and issues a token',function(){
    $r=$this->postJson('/api/auth/register',['business_name'=>'Fresh Company','name'=>'Ada','email'=>'ada@fresh.test','password'=>'password123','password_confirmation'=>'password123'])->assertCreated()->assertJsonPath('user.role','owner');
    expect(Business::count())->toBe(1);$this->withToken($r->json('token'))->getJson('/api/me')->assertOk()->assertJsonPath('business.name','Fresh Company');
});
it('logs in, rejects incorrect credentials and revokes logout tokens',function(){
    $this->seed();$this->postJson('/api/auth/login',['email'=>'owner@brightspark.test','password'=>'wrong'])->assertUnprocessable();
    $r=$this->postJson('/api/auth/login',['email'=>'owner@brightspark.test','password'=>'password'])->assertOk();
    $this->withToken($r->json('token'))->postJson('/api/auth/logout')->assertNoContent();expect(User::where('email','owner@brightspark.test')->first()->tokens()->count())->toBe(0);
});
it('requires authentication for tenant routes',fn()=>$this->getJson('/api/clients')->assertUnauthorized());