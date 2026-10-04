<?php
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default'=>'sqlite','database.connections.sqlite.database'=>$argv[1],'database.connections.sqlite.transaction_mode'=>'IMMEDIATE','database.connections.sqlite.busy_timeout'=>10000]);
\Illuminate\Support\Facades\DB::purge();
$business=(int)$argv[2];$user=(int)$argv[3];$client=(int)$argv[4];
for($n=0;$n<10;$n++) {
    \Illuminate\Support\Facades\DB::transaction(function()use($business,$user,$client){
        $number=app(\App\Services\InvoiceNumberService::class)->next($business);
        \App\Models\Invoice::withoutGlobalScopes()->create(['business_id'=>$business,'client_id'=>$client,'created_by'=>$user,'number'=>$number,'public_token'=>(string)\Illuminate\Support\Str::uuid(),'issue_date'=>today(),'due_date'=>today()->addDays(14),'tax_percent'=>0,'total_kobo'=>100,'subtotal_kobo'=>100]);
    },10);
}
echo "10 invoices created\n";