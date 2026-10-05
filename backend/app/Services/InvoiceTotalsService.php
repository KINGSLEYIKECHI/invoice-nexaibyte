<?php
namespace App\Services;
use Illuminate\Validation\ValidationException;
class InvoiceTotalsService {
    public function calculate(array $items,int $discount=0,string|float $taxPercent=0): array {
        $subtotal=0; $lines=[];
        foreach($items as $position=>$item) {
            $line=(int) bcdiv(bcadd(bcmul((string)$item['quantity'],(string)$item['unit_price_kobo'],4),'0.5',4),'1',0);
            $subtotal+=$line;if($subtotal>9007199254740991)throw ValidationException::withMessages(['items'=>'Document amount exceeds the supported safe-integer limit.']);
            $lines[]=array_merge($item,['line_total_kobo'=>$line,'position'=>$position]);
        }
        if($discount>$subtotal) throw ValidationException::withMessages(['discount_kobo'=>'Discount cannot exceed the subtotal.']);
        $tax=(int) bcdiv(bcadd(bcdiv(bcmul((string)($subtotal-$discount),(string)$taxPercent,4),'100',4),'0.5',4),'1',0);
        if($subtotal-$discount+$tax>9007199254740991)throw ValidationException::withMessages(['items'=>'Document total exceeds the supported safe-integer limit.']);
        return ['subtotal_kobo'=>$subtotal,'discount_kobo'=>$discount,'tax_kobo'=>$tax,'total_kobo'=>$subtotal-$discount+$tax,'items'=>$lines];
    }
}