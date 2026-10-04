<?php
use App\Models\Business;
use App\Services\InvoiceNumberService;
use Illuminate\Support\Facades\DB;
it('allocates sequential independent numbers and rolls back abandoned transactions',function(){
    $a=Business::create(['name'=>'A','slug'=>'a','invoice_prefix'=>'INV']);$b=Business::create(['name'=>'B','slug'=>'b','invoice_prefix'=>'KT']);$s=app(InvoiceNumberService::class);
    expect($s->next($a->id))->toBe('INV-0001')->and($s->next($a->id))->toBe('INV-0002')->and($s->next($b->id))->toBe('KT-0001');
    try{DB::transaction(function()use($s,$a){$s->next($a->id);throw new RuntimeException('Rollback');});}catch(RuntimeException){}
    expect($s->next($a->id))->toBe('INV-0003');
});