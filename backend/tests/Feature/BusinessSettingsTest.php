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
it('saves international currency and phone independently and validates bank account visibility',function(){
 $b=demoUser()->business;$data=$b->only(['name','email','phone','address','brand_color','currency','default_tax_percent','invoice_prefix','payment_instructions']);
 $data['currency']='JPY';$data['phone']='+442079460123';$data['bank_accounts']=[['bank_name'=>'Example bank','account_name'=>'Business account','account_number'=>'0012345678','routing_code'=>'EXAMPLE','details'=>'Transfer reference: invoice number','show_on_invoice'=>true,'show_on_quotation'=>false]];
 $this->putJson('/api/business',$data)->assertOk()->assertJsonPath('currency','JPY')->assertJsonPath('phone','+442079460123')->assertJsonPath('bank_accounts.0.account_number','0012345678');
 $business=$b->fresh();$html=view('pdf.bank-accounts',['business'=>$business,'visibility'=>'show_on_invoice'])->render();expect($html)->toContain('0012345678');expect(view('pdf.bank-accounts',['business'=>$business,'visibility'=>'show_on_quotation'])->render())->not->toContain('0012345678');
 $data['bank_accounts'][0]['account_number']='';$this->putJson('/api/business',$data)->assertUnprocessable();
 $this->actingAs(demoUser('staff@brightspark.test'));$this->putJson('/api/business',$data)->assertForbidden();
});
