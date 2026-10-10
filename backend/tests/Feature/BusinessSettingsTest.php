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
 $data['bank_accounts'][0]['account_number']='';$this->putJson('/api/business',$data)->assertOk();
 $this->actingAs(demoUser('staff@brightspark.test'));$this->putJson('/api/business',$data)->assertForbidden();
});

it('starts imported numbering formats and keeps PO optional on invoices and excludes it from quotations',function(){
 $b=demoUser()->business;$data=$b->only(['name','email','phone','address','brand_color','currency','default_tax_percent','invoice_prefix','payment_instructions']);$data['payment_instructions']='Transfer using invoice reference';$data['bank_accounts']=[['bank_name'=>'Receiving bank','account_number'=>'0012345','show_on_invoice'=>true,'show_on_quotation'=>false]];$data+=['next_invoice_number'=>125,'next_quotation_number'=>90,'invoice_separator'=>'/','invoice_padding'=>6,'quotation_prefix'=>'EST','quotation_separator'=>'','quotation_padding'=>3];
 $this->putJson('/api/business',$data)->assertOk()->assertJsonPath('next_invoice_number',125);
 $body=array_replace(invoiceBody(\App\Models\Client::first()->id),['due_date'=>'2099-01-01'])+['po_number'=>'PO-001','currency'=>'USD'];$this->postJson('/api/invoices',$body)->assertCreated()->assertJsonPath('number','INV/000125')->assertJsonPath('po_number','PO-001');
 $q=$this->postJson('/api/documents/quotations',$body)->assertCreated()->assertJsonPath('number','EST090')->json();$url='/api/documents/quotations/'.$q['id'];$this->postJson($url.'/transition',['action'=>'issue'])->assertOk();$this->postJson($url.'/transition',['action'=>'accept'])->assertOk();$this->postJson($url.'/convert')->assertCreated()->assertJsonPath('po_number',null)->assertJsonPath('number','INV/000126')->assertJsonPath('business.payment_instructions','Transfer using invoice reference')->assertJsonPath('business.bank_accounts.0.account_number','0012345');
 $this->putJson('/api/business',$data)->assertUnprocessable();
});

it('preserves a zero-prefixed starting number through its inferred format width',function(){
 $b=demoUser()->business;$data=$b->only(['name','email','phone','address','brand_color','currency','default_tax_percent','invoice_prefix','payment_instructions']);$data['next_invoice_number']='000132';$data['next_quotation_number']='000112';
 $this->putJson('/api/business',$data)->assertOk()->assertJsonPath('invoice_padding',6)->assertJsonPath('quotation_padding',6);
 $body=invoiceBody(\App\Models\Client::first()->id)+['currency'=>'USD'];$this->postJson('/api/invoices',$body)->assertCreated()->assertJsonPath('number','INV-000132');$this->postJson('/api/documents/quotations',$body+['po_number'=>'IGNORED'])->assertCreated()->assertJsonPath('number','QUO-000112')->assertJsonPath('po_number',null);
});
