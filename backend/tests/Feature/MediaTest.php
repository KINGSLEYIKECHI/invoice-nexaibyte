<?php
use App\Models\PlatformSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Http,Storage};
beforeEach(function(){$this->seed();$this->actingAs(demoUser());Storage::fake('public');config(['media.driver'=>'local']);});
it('serves local logos without symlinks and deletes replaced files',function(){
 $this->postJson('/api/business/logo',['logo'=>UploadedFile::fake()->image('logo.jpg',80,40)])->assertOk();$b=demoUser()->business;
 expect($b->logo_url)->toContain('/media/businesses/'.$b->id.'/logo?v=');$this->get('/media/businesses/'.$b->id.'/logo')->assertOk()->assertHeader('X-Content-Type-Options','nosniff');
 $old=$b->logo_path;$this->postJson('/api/business/logo',['logo'=>UploadedFile::fake()->image('new.png',40,40)])->assertOk();Storage::disk('public')->assertMissing($old);
});
it('requires platform access and preserves favicon metadata during text saves',function(){
 $this->postJson('/api/platform/images/favicon',['image'=>UploadedFile::fake()->image('icon.png',64,64)])->assertForbidden();
 $u=demoUser();$u->forceFill(['is_platform_admin'=>true])->save();$this->actingAs($u);
 $this->postJson('/api/platform/images/favicon',['image'=>UploadedFile::fake()->image('bad.png',64,32)])->assertUnprocessable();
 $this->postJson('/api/platform/images/favicon',['image'=>UploadedFile::fake()->image('icon.png',128,128)])->assertOk();$this->get('/media/platform/favicon')->assertOk();
 $settings=$this->getJson('/api/platform/settings')->json();expect($settings)->not->toHaveKey('favicon_asset');expect($settings['favicon_url'])->toContain('/media/platform/favicon?v=');
 $this->putJson('/api/platform/settings',PlatformSetting::defaults())->assertOk();expect(PlatformSetting::current()['favicon_url'])->not->toBe('');
 $this->postJson('/api/platform/images/logo',['image'=>UploadedFile::fake()->create('unsafe.svg',1,'image/svg+xml')])->assertUnprocessable();
});
it('uploads authenticated cloud images and embeds their bytes for PDFs',function(){
 config(['media.driver'=>'cloudinary','media.cloud_name'=>'test-cloud','media.api_key'=>'test-key','media.api_secret'=>'test-secret']);
 $fixture=UploadedFile::fake()->image('logo.png',40,40);$png=file_get_contents($fixture->getRealPath());
 Http::fake(function($r)use($png){if(str_ends_with($r->url(),'/image/upload')){$id=collect($r->data())->firstWhere('name','public_id')['contents'];return Http::response(['public_id'=>$id,'secure_url'=>'https://res.cloudinary.com/test-cloud/image/upload/v1/'.$id.'.png'],200);}if(str_starts_with($r->url(),'https://res.cloudinary.com/'))return Http::response($png,200);return Http::response(['result'=>'ok'],200);});
 $this->postJson('/api/business/logo',['logo'=>UploadedFile::fake()->image('logo.png',40,40)])->assertOk();$b=demoUser()->business;
 expect($b->logo_path)->toBeNull()->and($b->logo_url)->toStartWith('https://res.cloudinary.com/test-cloud/');expect(Storage::disk('public')->allFiles())->toBeEmpty();
 Http::assertSent(fn($r)=>str_ends_with($r->url(),'/image/upload')&&$r->hasHeader('Authorization','Basic '.base64_encode('test-key:test-secret')));

 expect(app(\App\Services\MediaStorage::class)->dataUri($b->logoAsset()))->toStartWith('data:image/png;base64,');
 $invoice=\App\Models\Invoice::firstOrFail();$this->get('/api/invoices/'.$invoice->id.'/pdf')->assertOk();
});
it('keeps existing logos on cloud failure and refuses unsafe PDF image URLs',function(){
 $this->postJson('/api/business/logo',['logo'=>UploadedFile::fake()->image('logo.png',40,40)])->assertOk();$old=demoUser()->business->logo_path;
 config(['media.driver'=>'cloudinary','media.cloud_name'=>'test-cloud','media.api_key'=>'key','media.api_secret'=>'secret']);Http::fake(['*'=>Http::response(['error'=>'quota'],400)]);
 $this->postJson('/api/business/logo',['logo'=>UploadedFile::fake()->image('new.png',40,40)])->assertUnprocessable();expect(demoUser()->business->logo_path)->toBe($old);Storage::disk('public')->assertExists($old);
 expect(app(\App\Services\MediaStorage::class)->dataUri(['driver'=>'cloudinary','url'=>'http://127.0.0.1/private']))->toBeNull();
});

