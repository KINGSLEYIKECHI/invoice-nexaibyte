<?php
use App\Models\{User,Invoice,Client};
beforeEach(function(){$this->seed();$this->actingAs(demoUser());});
it('keeps existing users advanced and saves only the current user selection',function(){
 $before=Invoice::count();$other=demoUser('admin@brightspark.test');$this->getJson('/api/workspace/preferences')->assertOk()->assertJsonPath('mode','advanced');
 $this->putJson('/api/workspace/preferences',['mode'=>'custom','enabled_modules'=>['products','statements']])->assertOk()->assertJsonPath('enabled_modules',['products','statements']);
 expect($other->fresh()->workspace_mode)->toBe('advanced');$this->getJson('/api/me')->assertJsonPath('enabled_modules',['products','statements']);
 $this->putJson('/api/workspace/preferences',['mode'=>'simple','enabled_modules'=>[]])->assertOk()->assertJsonPath('enabled_modules',['quotations']);expect(Invoice::count())->toBe($before);
 // Layout choices cannot disable server-side data or grant role permissions.
 $this->getJson('/api/products')->assertOk();$this->actingAs(demoUser('staff@brightspark.test'));$this->putJson('/api/workspace/preferences',['mode'=>'custom','enabled_modules'=>['team']])->assertUnprocessable();$this->putJson('/api/business',[])->assertForbidden();
});
it('rejects unknown modules and never accepts privilege or user identifiers',function(){
 $this->putJson('/api/workspace/preferences',['mode'=>'custom','enabled_modules'=>['bank_reconciliation']])->assertUnprocessable();$this->putJson('/api/workspace/preferences',['mode'=>'other','enabled_modules'=>[]])->assertUnprocessable();$this->putJson('/api/workspace/preferences',['mode'=>'custom','enabled_modules'=>['products','products']])->assertUnprocessable();
 $this->putJson('/api/workspace/preferences',['mode'=>'custom','enabled_modules'=>[],'user_id'=>demoUser('owner@kemtech.test')->id,'is_platform_admin'=>true])->assertOk();expect(demoUser()->fresh()->is_platform_admin)->toBeFalse()->and(demoUser('owner@kemtech.test')->workspace_mode)->toBe('advanced');
});
it('starts new registrations in simple mode without accepting requested privileges',function(){
 $r=$this->postJson('/api/auth/register',['business_name'=>'New workspace','name'=>'Owner','email'=>'modules-new@example.test','password'=>'password1234','password_confirmation'=>'password1234','workspace_mode'=>'advanced','is_platform_admin'=>true])->assertCreated()->assertJsonPath('user.workspace_mode','simple')->assertJsonPath('user.enabled_modules',['quotations']);
});
it('requires authentication for preferences and reports',function(){auth()->forgetGuards();$this->app['auth']->forgetGuards();$this->getJson('/api/workspace/preferences')->assertUnauthorized();$this->getJson('/api/reports/ageing')->assertUnauthorized();});

it('lets new team members start simple without changing their assigned role',function(){
 $this->postJson('/api/team',['name'=>'New staff','email'=>'module-staff@example.test','password'=>'password1234','role'=>'staff'])->assertCreated()->assertJsonPath('workspace_mode','simple')->assertJsonPath('enabled_modules',['quotations'])->assertJsonPath('role','staff');
});
