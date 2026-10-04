<?php
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$path=storage_path('framework/testing-concurrency-'.bin2hex(random_bytes(6)).'.sqlite');
touch($path);
try {
    config(['database.default'=>'sqlite','database.connections.sqlite.database'=>$path,'database.connections.sqlite.transaction_mode'=>'IMMEDIATE','database.connections.sqlite.busy_timeout'=>10000]);
    \Illuminate\Support\Facades\DB::purge();
    \Illuminate\Support\Facades\Artisan::call('migrate',['--force'=>true]);
    $b=\App\Models\Business::create(['name'=>'Concurrency test','slug'=>'concurrency-test','invoice_prefix'=>'INV']);
    $u=\App\Models\User::create(['business_id'=>$b->id,'name'=>'Test','email'=>'test@concurrency.test','password'=>'testpassword','role'=>'owner']);
    $c=\App\Models\Client::withoutGlobalScopes()->create(['business_id'=>$b->id,'name'=>'Test Client']);
    $workers=[];
    for($n=0;$n<4;$n++) {
        $p=new \Symfony\Component\Process\Process([PHP_BINARY,__DIR__.'/concurrency-worker.php',$path,(string)$b->id,(string)$u->id,(string)$c->id],base_path());
        $p->setTimeout(60);$p->start();$workers[]=$p;
    }
    foreach($workers as $worker) {$worker->wait();if(!$worker->isSuccessful() || !str_contains($worker->getOutput(),'10 invoices created')) throw new RuntimeException($worker->getErrorOutput().$worker->getOutput());}
    $numbers=\App\Models\Invoice::withoutGlobalScopes()->where('business_id',$b->id)->orderBy('number')->pluck('number')->all();
    $expected=array_map(fn($n)=>'INV-'.str_pad((string)$n,4,'0',STR_PAD_LEFT),range(1,40));
    if($numbers!==$expected)throw new RuntimeException('Concurrent numbers were not unique and sequential: '.json_encode($numbers));
    echo "PASS: 4 concurrent workers created 40 invoices with unique sequential numbers.\n";
} finally {
    \Illuminate\Support\Facades\DB::disconnect();
    if(is_file($path))unlink($path);
}