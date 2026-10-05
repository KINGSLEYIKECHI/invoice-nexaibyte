<?php
namespace App\Services;
use Illuminate\Validation\ValidationException;
class CurrencyService {
 public function all():array{return json_decode(file_get_contents(resource_path('data/currencies.json')),true,512,JSON_THROW_ON_ERROR);}
 public function codes():array{return array_column($this->all(),'code');}
 public function precision(string $code):int {foreach($this->all() as $c)if($c['code']===$code)return $c['minor_units'];throw ValidationException::withMessages(['currency'=>'Select a supported currency.']);}
 public function minor(string $value,string $currency):int {
  $digits=$this->precision($currency);if(!preg_match('/^\d+(?:\.\d{1,'.max(1,$digits).'})?$/D',$value)||($digits===0&&str_contains($value,'.')))throw ValidationException::withMessages(['unit_price'=>'Price has invalid decimal precision for '.$currency.'.']);
  $minor=bcmul($value,(string)(10**$digits),0);if(bccomp($minor,'100000000000',0)>0)throw ValidationException::withMessages(['unit_price'=>'Price exceeds the supported limit.']);return (int)$minor;
 }
 public function format(int $minor,string $currency='NGN',?int $digits=null):string {$digits??=$this->precision($currency);return $currency.' '.number_format($minor/(10**$digits),$digits);}
}
