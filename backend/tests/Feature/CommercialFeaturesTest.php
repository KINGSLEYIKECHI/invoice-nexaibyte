<?php
use App\Models\{Invoice,Product,CommercialDocument};
use App\Services\{CurrencyService,ProductImportService};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
beforeEach(function(){$this->seed();$this->actingAs(demoUser());Queue::fake();});
function commercialBody(string $currency='USD'):array{return invoiceBody(\App\Models\Client::first()->id)+['currency'=>$currency];}
function csvUpload(string $rows):UploadedFile{return UploadedFile::fake()->createWithContent('products.csv',implode(',',ProductImportService::HEADERS)."\n".$rows);}
it('snapshots currencies with global decimal precision and separated dashboard totals',function(){
 $s=app(CurrencyService::class);expect(count($s->all()))->toBeGreaterThan(150)->and($s->precision('JPY'))->toBe(0)->and($s->precision('KWD'))->toBe(3)->and($s->precision('CLF'))->toBe(4)->and($s->minor('1.234','KWD'))->toBe(1234);expect(fn()=>$s->minor('1.23','JPY'))->toThrow(\Illuminate\Validation\ValidationException::class);
 $old=Invoice::first();demoUser()->business->update(['currency'=>'USD']);$this->postJson('/api/invoices',invoiceBody(\App\Models\Client::first()->id))->assertCreated()->assertJsonPath('currency','USD');expect($old->fresh()->currency)->toBe('NGN');$usd=Invoice::where('currency','USD')->first();$usd->update(['status'=>'sent','sent_at'=>now()]);$r=$this->getJson('/api/dashboard')->assertOk()->assertJsonPath('currency','USD');expect($r->json('billed_kobo'))->toBe($usd->total_kobo)->and(count($r->json('currency_totals')))->toBe(2);$this->postJson('/api/invoices',commercialBody('XYZ'))->assertUnprocessable();
});
it('accepts quotations and converts once to a currency-preserving invoice',function(){
 $body=commercialBody('KWD');$q=$this->postJson('/api/documents/quotations',$body)->assertCreated()->assertJsonPath('currency_minor_units',3)->json();$url='/api/documents/quotations/'.$q['id'];$this->postJson($url.'/convert')->assertUnprocessable();$this->postJson($url.'/transition',['action'=>'issue'])->assertOk();$this->putJson($url,$body)->assertUnprocessable();$this->postJson($url.'/transition',['action'=>'accept'])->assertOk();$invoice=$this->postJson($url.'/convert')->assertCreated()->assertJsonPath('currency','KWD')->assertJsonPath('status','draft')->json();$this->postJson($url.'/convert')->assertOk()->assertJsonPath('id',$invoice['id']);$this->get($url.'/pdf')->assertOk();
});
it('keeps delivery notes price-free and records receipt or creates them from invoices',function(){
 $d=$this->postJson('/api/documents/delivery-notes',commercialBody())->assertCreated()->assertJsonPath('total_kobo',0)->assertJsonPath('items.0.unit_price_kobo',0)->json();$url='/api/documents/delivery-notes/'.$d['id'];$this->postJson($url.'/convert')->assertUnprocessable();$this->postJson($url.'/transition',['action'=>'issue'])->assertOk();$this->postJson($url.'/transition',['action'=>'deliver'])->assertUnprocessable();$this->postJson($url.'/transition',['action'=>'deliver','received_by'=>'Recipient'])->assertOk()->assertJsonPath('status','delivered');$this->get($url.'/pdf')->assertOk();$invoice=Invoice::where('status','sent')->first();$this->postJson('/api/invoices/'.$invoice->id.'/delivery-note')->assertCreated()->assertJsonPath('invoice_id',$invoice->id);
});
it('isolates products and documents and restricts imports to managers',function(){
 $d=$this->postJson('/api/documents/quotations',commercialBody())->assertCreated()->json();$p=$this->postJson('/api/products',['sku'=>'sku-01','name'=>'Item','unit'=>'pcs','unit_price'=>'12.34','currency'=>'USD','is_active'=>true])->assertCreated()->assertJsonPath('sku','SKU-01')->json();$this->actingAs(demoUser('owner@kemtech.test'));$this->getJson('/api/documents/quotations/'.$d['id'])->assertNotFound();$this->getJson('/api/products/'.$p['id'])->assertNotFound();$this->getJson('/api/products')->assertJsonPath('total',0);$this->actingAs(demoUser('staff@brightspark.test'));$this->postJson('/api/products/import',['file'=>csvUpload('A,Item,,pcs,1.00,USD,1'),'preview'=>true])->assertForbidden();
});
it('previews and imports CSV atomically with explicit SKU updates',function(){
 $file=csvUpload("A,Item,,pcs,1.234,KWD,1\nB,Other,,pcs,100,JPY,1");$this->postJson('/api/products/import',['file'=>$file,'preview'=>true])->assertOk()->assertJsonPath('can_import',true);expect(Product::count())->toBe(0);$this->postJson('/api/products/import',['file'=>$file,'preview'=>false])->assertOk()->assertJsonPath('created',2);expect(Product::where('sku','A')->first()->unit_price_minor)->toBe(1234);$this->postJson('/api/products/import',['file'=>$file,'preview'=>false])->assertUnprocessable();$this->postJson('/api/products/import',['file'=>csvUpload('a,Updated,,pcs,9.00,USD,1'),'preview'=>false,'update_existing'=>true])->assertOk()->assertJsonPath('updated',1);$this->postJson('/api/products/import',['file'=>csvUpload("C,Good,,pcs,1.00,USD,1\nD,Bad,,pcs,1.50,JPY,1"),'preview'=>false])->assertUnprocessable();expect(Product::where('sku','C')->exists())->toBeFalse();
});
it('roundtrips Excel templates and refuses formula cells',function(){
 [$bytes]=app(ProductImportService::class)->template('xlsx','USD');$this->postJson('/api/products/import',['file'=>UploadedFile::fake()->createWithContent('products.xlsx',$bytes),'preview'=>true])->assertOk()->assertJsonPath('can_import',true);$this->get('/api/products/template/xlsx')->assertOk();$path=tempnam(sys_get_temp_dir(),'formula-');file_put_contents($path,$bytes);$zip=new \ZipArchive();$zip->open($path);$xml=$zip->getFromName('xl/worksheets/sheet1.xml');$zip->addFromString('xl/worksheets/sheet1.xml',str_replace('<sheetData>','<sheetData><row r="99"><c r="A99"><f>1+1</f><v>2</v></c></row>',$xml));$zip->close();$this->postJson('/api/products/import',['file'=>UploadedFile::fake()->createWithContent('bad.xlsx',file_get_contents($path)),'preview'=>true])->assertUnprocessable();unlink($path);
});

it('rejects totals beyond safe integer precision including tax',function(){
 $items=array_fill(0,50,['description'=>'Large amount','quantity'=>100000,'unit_price_kobo'=>1000000000]);
 expect(fn()=>app(\App\Services\InvoiceTotalsService::class)->calculate($items,0,100))->toThrow(\Illuminate\Validation\ValidationException::class);
});
