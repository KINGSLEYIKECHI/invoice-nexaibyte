<?php
use App\Models\{Business,User,Invoice};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
beforeEach(function(){$this->seed();$this->actingAs(demoUser());});
it('updates brand settings and uploads validated logos',function(){
    Storage::fake('public');$b=demoUser()->business;
    $data=$b->only(['name','email','phone','address','brand_color','currency','default_tax_percent','invoice_prefix','payment_instructions']);
    $data['brand_color']='#123456';$this->putJson('/api/business',$data)->assertOk()->assertJsonPath('brand_color','#123456');
    $this->postJson('/api/business/logo',['logo'=>UploadedFile::fake()->image('logo.png',100,100)])->assertOk();Storage::disk('public')->assertExists($b->fresh()->logo_path);
    $this->postJson('/api/business/logo',['logo'=>UploadedFile::fake()->create('bad.txt',2)])->assertUnprocessable();
});
it('requires owner permission and exact business name before deleting only that tenant',function(){
    $owner=demoUser();$this->actingAs(demoUser('admin@brightspark.test'));
    $this->deleteJson('/api/business',['business_name'=>'Bright Spark Electricals'])->assertForbidden();
    $this->actingAs($owner);$this->deleteJson('/api/business',['business_name'=>'Wrong name'])->assertUnprocessable();
    $this->deleteJson('/api/business',['business_name'=>'Bright Spark Electricals'])->assertNoContent();
    expect(Business::count())->toBe(1)->and(User::count())->toBe(1);
    expect(Invoice::withoutGlobalScopes()->count())->toBe(1);
});