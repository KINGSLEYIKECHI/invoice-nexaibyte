<?php
namespace App\Http\Controllers;
use App\Services\AdvertisingSettings;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
class AdvertisingController extends Controller {
 public function show(AdvertisingSettings $ads){return $ads->current();}
 public function update(Request $r,AdvertisingSettings $ads){
  $d=$r->validate(['enabled'=>'required|boolean','approved'=>'required|boolean','consent_ready'=>'required|boolean','publisher_id'=>'nullable|string|regex:/^ca-pub-[0-9]{16}$/','slot_id'=>'nullable|string|regex:/^[0-9]{1,20}$/']);$d['publisher_id']??='';$d['slot_id']??='';
  if($d['enabled']&&(!$d['approved']||!$d['consent_ready']||!$d['publisher_id']||!$d['slot_id']))throw ValidationException::withMessages(['enabled'=>'Approval, publisher ID, slot ID and an integrated consent manager are required before enabling ads.']);
  return $ads->save($d);
 }
 public function adsTxt(AdvertisingSettings $ads){$v=$ads->current();$text=$v['publisher_id']?'google.com, '.substr($v['publisher_id'],3).", DIRECT, f08c47fec0942fa0\n":"# No advertising publisher configured.\n";return response($text,200,['Content-Type'=>'text/plain; charset=UTF-8','Cache-Control'=>'no-cache','X-Content-Type-Options'=>'nosniff']);}
}
