<?php
use Symfony\Component\Process\Process;
it('creates unique sequential invoices from four concurrent processes',function(){
    $p=new Process([PHP_BINARY,base_path('scripts/check-concurrency.php')],base_path());$p->setTimeout(90);$p->run();
    expect($p->isSuccessful(),$p->getErrorOutput().$p->getOutput())->toBeTrue();
    expect($p->getOutput())->toContain('40 invoices');
});