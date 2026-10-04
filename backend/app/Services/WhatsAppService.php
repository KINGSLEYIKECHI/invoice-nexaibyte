<?php
namespace App\Services;
use App\Models\{Invoice,MessageLog};
use Illuminate\Support\Facades\{Http,Log,URL};
class WhatsAppService {
    public function send(Invoice $invoice,MessageLog $log): ?string {
        $c=config('services.whatsapp');
        $payload=['messaging_product'=>'whatsapp','to'=>ltrim($log->recipient,'+'),'type'=>'document','document'=>['link'=>URL::signedRoute('public.invoice.pdf',['public_token'=>$invoice->public_token]),'filename'=>$invoice->number.'.pdf','caption'=>$log->message ?: 'Your invoice '.$invoice->number]];
        if(!$c['enabled'] || !$c['token'] || !$c['phone_id']) {
            Log::info('WhatsApp log-only delivery',['invoice_id'=>$invoice->id,'payload'=>$payload]);
            return 'log-only-'.$log->id;
        }
        return Http::withToken($c['token'])->timeout(30)->post($c['base_url'].'/'.$c['version'].'/'.$c['phone_id'].'/messages',$payload)->throw()->json('messages.0.id');
    }
}