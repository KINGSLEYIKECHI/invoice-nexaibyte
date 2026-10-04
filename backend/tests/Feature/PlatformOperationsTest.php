<?php
use App\Models\{IntegrationSetting,ActivityLog,PaymentNotification,Invoice};
use App\Services\IntegrationSettings;
use App\Jobs\SendPaymentEmail;
use Illuminate\Support\Facades\{DB,Http,Mail,Queue};
function platformAdmin(){ $u=demoUser();$u->forceFill(['is_platform_admin'=>true])->save();return $u; }
function integrationBody():array{return array_replace(app(IntegrationSettings::class)->safe(),['mail_driver'=>'smtp','smtp_host'=>'smtp.hostinger.com','smtp_port'=>587,'smtp_security'=>'tls','smtp_username'=>'billing@example.test','smtp_password'=>'smtp-test-secret','mail_from_address'=>'billing@example.test','mail_from_name'=>'Test service','media_driver'=>'cloudinary','cloudinary_cloud_name'=>'test-cloud','cloudinary_api_key'=>'cloud-test-key','cloudinary_api_secret'=>'cloud-test-secret','cloudinary_folder'=>'invoice-saas','whatsapp_enabled'=>true,'whatsapp_phone_id'=>'12345','whatsapp_token'=>'whatsapp-test-secret','whatsapp_version'=>'v23.0']);}
beforeEach(function(){$this->seed();});
it('restricts every administration endpoint to explicit platform administrators',function(){
 foreach(['/api/platform/integrations','/api/platform/users','/api/platform/overview','/api/platform/activity','/api/platform/payment-emails'] as $url){$this->getJson($url)->assertUnauthorized();}
 $this->actingAs(demoUser());foreach(['/api/platform/integrations','/api/platform/users','/api/platform/overview','/api/platform/activity','/api/platform/payment-emails'] as $url)$this->getJson($url)->assertForbidden();
 $this->putJson('/api/platform/integrations',integrationBody())->assertForbidden();$this->postJson('/api/platform/integrations/test/smtp',['recipient'=>'test@example.test'])->assertForbidden();$this->putJson('/api/platform/advertising',[])->assertForbidden();
});
it('encrypts secrets masks them and preserves or explicitly clears credentials',function(){
 $this->actingAs(platformAdmin());$body=integrationBody();$response=$this->putJson('/api/platform/integrations',$body)->assertOk()->assertJsonPath('has_smtp_password',true);expect($response->json())->not->toHaveKey('smtp_password');
 $raw=DB::table('integration_settings')->value('settings');foreach(IntegrationSettings::SECRETS as $key)expect($raw)->not->toContain($body[$key]);
 expect(IntegrationSetting::find(1)->settings['smtp_password'])->toBe('smtp-test-secret');
 $body['smtp_password']='';$this->putJson('/api/platform/integrations',$body)->assertOk()->assertJsonPath('has_smtp_password',true);
 $body['clear_smtp_password']=true;$this->putJson('/api/platform/integrations',$body)->assertOk()->assertJsonPath('has_smtp_password',false);
 $public=$this->getJson('/api/platform/settings')->assertOk()->json();expect(json_encode($public))->not->toContain('smtp-test-secret');
 $this->getJson('/api/platform/activity')->assertOk();expect(json_encode(ActivityLog::all()->toArray()))->not->toContain('smtp-test-secret')->not->toContain('whatsapp-test-secret');
});
it('applies database configuration even with environment fallback and tests providers without exposing errors',function(){
 $this->actingAs(platformAdmin());$this->putJson('/api/platform/integrations',integrationBody())->assertOk();expect(config('mail.mailers.smtp.host'))->toBe('smtp.hostinger.com')->and(config('mail.mailers.smtp.require_tls'))->toBeTrue()->and(config('media.api_secret'))->toBe('cloud-test-secret');
 Http::fake(['https://api.cloudinary.com/*'=>Http::response(['credits'=>[]]),'https://graph.facebook.com/*'=>Http::response(['id'=>'12345'])]);
 $this->postJson('/api/platform/integrations/test/cloudinary')->assertOk();$this->postJson('/api/platform/integrations/test/whatsapp')->assertOk();
 Http::assertSent(fn($r)=>$r->hasHeader('Authorization','Basic '.base64_encode('cloud-test-key:cloud-test-secret')));
 Http::swap(new \Illuminate\Http\Client\Factory());Http::fake(['*'=>Http::response(['error'=>'smtp-test-secret'],401)]);$result=$this->postJson('/api/platform/integrations/test/cloudinary')->assertUnprocessable();expect($result->getContent())->not->toContain('smtp-test-secret');
 Mail::fake();$this->postJson('/api/platform/integrations/test/smtp',['recipient'=>'recipient@example.test'])->assertOk();
});
it('counts distinct logins across Lagos midnight and lists users across businesses without credentials',function(){
 $u=platformAdmin();$this->actingAs($u);$this->travelTo(\Carbon\Carbon::parse('2026-10-04 23:30:00','UTC'));
 ActivityLog::create(['user_id'=>$u->id,'business_id'=>$u->business_id,'event'=>'auth.login','created_at'=>'2026-10-04 22:30:00']);
 ActivityLog::create(['user_id'=>$u->id,'business_id'=>$u->business_id,'event'=>'auth.login','created_at'=>'2026-10-04 23:10:00']);
 ActivityLog::create(['user_id'=>$u->id,'business_id'=>$u->business_id,'event'=>'auth.login','created_at'=>'2026-10-04 23:20:00']);
 $r=$this->getJson('/api/platform/overview?days=2')->assertOk()->assertJsonPath('daily.0.logged_in_users',1)->assertJsonPath('daily.1.logged_in_users',1);
 $users=$this->getJson('/api/platform/users')->assertOk();expect($users->json('total'))->toBe(4);expect($users->json('data.0'))->not->toHaveKey('password');
 $this->getJson('/api/platform/users?search=kemtech')->assertJsonPath('total',1);
});
it('records login registration and successful changes without recording submitted fields',function(){
 $this->postJson('/api/auth/login',['email'=>'owner@brightspark.test','password'=>'password'])->assertOk();expect(ActivityLog::where('event','auth.login')->count())->toBe(1);
 $this->actingAs(demoUser());$this->putJson('/api/business',['name'=>'Changed','currency'=>'NGN','email'=>'owner@brightspark.test','phone'=>'','address'=>'','brand_color'=>'#123456','invoice_prefix'=>'INV','default_tax_percent'=>0])->assertOk();
 expect(ActivityLog::where('event','PUT api/business')->exists())->toBeTrue();expect(json_encode(ActivityLog::all()->toArray()))->not->toContain('password')->not->toContain('Changed');
});
it('queues owner and customer payment notifications after commit with exact recorded balance and no duplicates on reprocessing',function(){
 Queue::fake();$this->actingAs(demoUser());$i=Invoice::where('status','sent')->first();$this->postJson('/api/invoices/'.$i->id.'/payments',['method'=>'cash','paid_on'=>today()->format('Y-m-d'),'amount_kobo'=>100000])->assertCreated();
 expect(PaymentNotification::count())->toBe(2);Queue::assertPushed(SendPaymentEmail::class,2);$n=PaymentNotification::where('kind','owner')->first();expect($n->recipient)->toBe('owner@brightspark.test')->and($n->details['balance_kobo'])->toBe($i->total_kobo-100000);
 Mail::fake();$job=new SendPaymentEmail($n->id,$n->business_id);$job->handle();expect($n->fresh()->status)->toBe('sent')->and($n->fresh()->attempts)->toBe(1);$job->handle();expect($n->fresh()->attempts)->toBe(1);
 $this->deleteJson('/api/payments/'.$n->payment_id)->assertOk();expect(PaymentNotification::count())->toBe(0);$job->handle();
});
it('honours disabled payment recipients and does not queue rejected payments',function(){
 Queue::fake();app(IntegrationSettings::class)->save(['payment_owner_email'=>false,'payment_client_email'=>false]);$this->actingAs(demoUser());$i=Invoice::where('status','sent')->first();$p=['method'=>'cash','paid_on'=>today()->format('Y-m-d')];
 $this->postJson('/api/invoices/'.$i->id.'/payments',$p+['amount_kobo'=>$i->total_kobo+1])->assertUnprocessable();$this->postJson('/api/invoices/'.$i->id.'/payments',$p+['amount_kobo'=>100])->assertCreated();expect(PaymentNotification::count())->toBe(0);Queue::assertNothingPushed();
});
it('keeps ads disabled validates publisher IDs and requires explicit approval and consent readiness',function(){
 $this->getJson('/api/advertising')->assertOk()->assertJsonPath('enabled',false);$this->get('/ads.txt')->assertOk()->assertHeader('Content-Type','text/plain; charset=UTF-8');$this->actingAs(platformAdmin());
 $body=['enabled'=>true,'approved'=>false,'consent_ready'=>false,'publisher_id'=>'ca-pub-1234567890123456','slot_id'=>'1234'];$this->putJson('/api/platform/advertising',$body)->assertUnprocessable();
 $body['enabled']=false;$this->putJson('/api/platform/advertising',$body)->assertOk();$this->get('/ads.txt')->assertSee('google.com, pub-1234567890123456, DIRECT, f08c47fec0942fa0');
 $body['publisher_id']='javascript:alert(1)';$this->putJson('/api/platform/advertising',$body)->assertUnprocessable();
});

it('preserves recorded payments on mail failure exposes only generic errors and restricts retries',function(){
 Queue::fake();$this->actingAs(demoUser());$i=Invoice::where('status','sent')->first();$this->postJson('/api/invoices/'.$i->id.'/payments',['method'=>'cash','paid_on'=>today()->format('Y-m-d'),'amount_kobo'=>100])->assertCreated();$n=PaymentNotification::first();
 Mail::shouldReceive('raw')->once()->andThrow(new \RuntimeException('secret-provider-password'));
 $job=new SendPaymentEmail($n->id,$n->business_id);expect(fn()=>$job->handle())->toThrow(\RuntimeException::class,'Payment email delivery failed. Check SMTP settings.');expect($n->fresh()->error)->not->toContain('secret-provider-password');expect(\App\Models\Payment::count())->toBeGreaterThan(0);
 $job->failed(null);$this->postJson('/api/platform/payment-emails/'.$n->id.'/retry')->assertForbidden();$this->actingAs(platformAdmin());$this->postJson('/api/platform/payment-emails/'.$n->id.'/retry')->assertOk();expect($n->fresh()->status)->toBe('pending');
});
it('prunes only activity metadata older than retention and preserves account records',function(){
 $u=demoUser();ActivityLog::create(['user_id'=>$u->id,'business_id'=>$u->business_id,'event'=>'auth.login','created_at'=>now()->subDays(91)]);ActivityLog::record($u,'auth.login');$this->artisan('platform:prune-activity')->assertSuccessful();expect(ActivityLog::count())->toBe(1)->and(\App\Models\User::count())->toBe(4);
});
