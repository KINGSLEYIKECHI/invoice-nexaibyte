<?php
use App\Services\InvoiceTotalsService;
it('rounds each fractional line and tax in integer kobo',function(){
    $r=(new InvoiceTotalsService)->calculate([['description'=>'A','quantity'=>1.25,'unit_price_kobo'=>101],['description'=>'B','quantity'=>0.5,'unit_price_kobo'=>1]],1,7.5);
    expect($r['subtotal_kobo'])->toBe(127)->and($r['tax_kobo'])->toBe(9)->and($r['total_kobo'])->toBe(135);
});
it('handles exact half rounding and zero totals',function(){
    $s=new InvoiceTotalsService;
    expect($s->calculate([['quantity'=>1,'unit_price_kobo'=>100]],0,7.5)['tax_kobo'])->toBe(8);
    expect($s->calculate([['quantity'=>1,'unit_price_kobo'=>100]],100,7.5)['total_kobo'])->toBe(0);
});