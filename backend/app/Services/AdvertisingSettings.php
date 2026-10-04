<?php
namespace App\Services;
use App\Models\PlatformSetting;
use Illuminate\Support\Facades\DB;
class AdvertisingSettings {
 public function defaults():array{return ['enabled'=>false,'approved'=>false,'publisher_id'=>'','slot_id'=>'','consent_ready'=>false];}
 public function current():array{return array_replace($this->defaults(),array_intersect_key(PlatformSetting::find(1)?->settings['advertising']??[],$this->defaults()));}
 public function save(array $data):array {DB::transaction(function()use($data){PlatformSetting::firstOrCreate(['id'=>1],['settings'=>PlatformSetting::defaults()]);$row=PlatformSetting::lockForUpdate()->findOrFail(1);$v=$row->settings;$v['advertising']=$data;$row->update(['settings'=>$v]);},5);return $this->current();}
}
