<?php
use App\Models\{Invoice,MessageLog};
use App\Jobs\{SendInvoiceEmail,SendInvoiceWhatsApp};
use App\Mail\InvoiceMail;
use App\Services\{InvoicePdfService,WhatsAppService};
use Illuminate\Support\Facades\{Queue,Mail,Http};
beforeEach(function(){$this->seed();$this->actingAs(demoUser());});
it('queues one job per channel with queued delivery logs',function(){
    Queue::fake();$i=Invoice::where('status','draft')->first();
    $this->postJson('/api/invoices/'.$i->id.'/send',['channels'=>['email','whatsapp'],'message'=>'Hello'])->assertAccepted()->assertJsonCount(2,'data');
    Queue::assertPushed(SendInvoiceEmail::class,1);Queue::assertPushed(SendInvoiceWhatsApp::class,1);
    expect($i->messages()->where('status','queued')->count())->toBe(2);
    $this->putJson('/api/invoices/'.$i->id,invoiceBody($i->client_id))->assertUnprocessable();
});
it('sends mail with a PDF and marks the draft sent on success',function(){
    Queue::fake();Mail::fake();$i=Invoice::where('status','draft')->first();
    $r=$this->postJson('/api/invoices/'.$i->id.'/send',['channels'=>['email']])->assertAccepted();
    $job=new SendInvoiceEmail($r->json('data.0.id'),$i->business_id);auth()->forgetGuards();
    $job->handle(app(InvoicePdfService::class),app(WhatsAppService::class));
    Mail::assertSent(InvoiceMail::class,fn($mail)=>str_starts_with($mail->pdf,'%PDF-')&&count($mail->attachments())===1&&$mail->invoice->number===$i->number);
    expect(Invoice::withoutGlobalScopes()->find($i->id)->status)->toBe('sent');
});
it('supports log-only WhatsApp and the Cloud document API',function(){
    Queue::fake();$i=Invoice::where('status','draft')->first();
    $r=$this->postJson('/api/invoices/'.$i->id.'/send',['channels'=>['whatsapp']])->assertAccepted();
    $job=new SendInvoiceWhatsApp($r->json('data.0.id'),$i->business_id);
    config(['services.whatsapp.enabled'=>false]);$job->handle(app(InvoicePdfService::class),app(WhatsAppService::class));
    expect(MessageLog::find($job->logId)->provider_message_id)->toStartWith('log-only');
    $r=$this->postJson('/api/invoices/'.$i->id.'/send',['channels'=>['whatsapp']]);$job=new SendInvoiceWhatsApp($r->json('data.0.id'),$i->business_id);
    config(['services.whatsapp.enabled'=>true,'services.whatsapp.token'=>'test','services.whatsapp.phone_id'=>'123']);
    Http::fake(['*'=>Http::response(['messages'=>[['id'=>'meta-123']]])]);$job->handle(app(InvoicePdfService::class),app(WhatsAppService::class));
    Http::assertSent(fn($r)=>$r['type']==='document'&&str_contains($r['document']['link'],'signature='));
    expect(MessageLog::find($job->logId)->provider_message_id)->toBe('meta-123');
});
it('tracks attempts retries and final delivery failure',function(){
    Queue::fake();$i=Invoice::where('status','draft')->first();$r=$this->postJson('/api/invoices/'.$i->id.'/send',['channels'=>['whatsapp']]);
    config(['services.whatsapp.enabled'=>true,'services.whatsapp.token'=>'test','services.whatsapp.phone_id'=>'123']);Http::fake(['*'=>Http::response(['error'=>'Unavailable'],503)]);
    $job=new SendInvoiceWhatsApp($r->json('data.0.id'),$i->business_id);$error=null;
    for($n=0;$n<3;$n++){try{$job->handle(app(InvoicePdfService::class),app(WhatsAppService::class));}catch(Throwable $e){$error=$e;}}
    $job->failed($error);$log=MessageLog::find($job->logId);
    expect($job->tries)->toBe(3)->and($job->backoff())->toBe([30,120,600])->and($log->status)->toBe('failed')->and($log->attempts)->toBe(3)->and($log->error)->not->toBeNull();
    expect($i->fresh()->status)->toBe('draft');
});
it('atomically rejects sending when one selected contact is missing',function(){
    Queue::fake();$i=Invoice::where('status','draft')->first();$i->client->update(['phone'=>null]);
    $this->postJson('/api/invoices/'.$i->id.'/send',['channels'=>['email','whatsapp']])->assertUnprocessable();expect($i->messages()->count())->toBe(0);Queue::assertNothingPushed();
});