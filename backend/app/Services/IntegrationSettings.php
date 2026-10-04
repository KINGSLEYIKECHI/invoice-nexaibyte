<?php
namespace App\Services;
use App\Models\IntegrationSetting;
use Illuminate\Support\Facades\{DB,Mail,Schema};
class IntegrationSettings {
 public const SECRETS=['smtp_password','cloudinary_api_key','cloudinary_api_secret','whatsapp_token'];
 public function defaults():array {return [
  'mail_driver'=>config('mail.default'),'smtp_host'=>config('mail.mailers.smtp.host'),'smtp_port'=>(int)config('mail.mailers.smtp.port'),'smtp_security'=>(int)config('mail.mailers.smtp.port')===465?'ssl':'tls','smtp_username'=>config('mail.mailers.smtp.username')??'','smtp_password'=>config('mail.mailers.smtp.password')??'',
  'mail_from_address'=>config('mail.from.address'),'mail_from_name'=>config('mail.from.name'),
  'media_driver'=>config('media.driver'),'cloudinary_cloud_name'=>config('media.cloud_name')??'','cloudinary_api_key'=>config('media.api_key')??'','cloudinary_api_secret'=>config('media.api_secret')??'','cloudinary_folder'=>config('media.folder','invoice-saas'),
  'whatsapp_enabled'=>(bool)config('services.whatsapp.enabled'),'whatsapp_phone_id'=>config('services.whatsapp.phone_id')??'','whatsapp_token'=>config('services.whatsapp.token')??'','whatsapp_version'=>config('services.whatsapp.version','v23.0'),
  'payment_owner_email'=>true,'payment_client_email'=>true
 ];}
 public function values():array {return array_replace($this->defaults(),IntegrationSetting::find(1)?->settings??[]);}
 public function safe():array {
  $v=$this->values();foreach(self::SECRETS as $key){$v['has_'.$key]=!empty($v[$key]);unset($v[$key]);}return $v;
 }
 public function save(array $data):void {
  DB::transaction(function()use($data){IntegrationSetting::firstOrCreate(['id'=>1],['settings'=>[]]);$row=IntegrationSetting::lockForUpdate()->findOrFail(1);$values=$row->settings;
   foreach(self::SECRETS as $key){if(!empty($data['clear_'.$key]))$data[$key]='';elseif(empty($data[$key]))unset($data[$key]);unset($data['clear_'.$key]);}
   $row->update(['settings'=>array_replace($values,$data)]);
  },5);
 }
 public function apply():void {
  // During first installation the table does not exist yet. Otherwise fail closed on DB/decryption errors.
  if(!Schema::hasTable('integration_settings')||!IntegrationSetting::find(1))return;
  $v=$this->values();
  config(['mail.default'=>$v['mail_driver'],'mail.from'=>['address'=>$v['mail_from_address'],'name'=>$v['mail_from_name']],
   'mail.mailers.smtp'=>['transport'=>'smtp','host'=>$v['smtp_host'],'port'=>$v['smtp_port'],'scheme'=>$v['smtp_security']==='ssl'?'smtps':'smtp','require_tls'=>true,'username'=>$v['smtp_username'],'password'=>$v['smtp_password'],'timeout'=>15],
   'media.driver'=>$v['media_driver'],'media.cloud_name'=>$v['cloudinary_cloud_name'],'media.api_key'=>$v['cloudinary_api_key'],'media.api_secret'=>$v['cloudinary_api_secret'],'media.folder'=>$v['cloudinary_folder'],
   'services.whatsapp'=>['enabled'=>$v['whatsapp_enabled'],'phone_id'=>$v['whatsapp_phone_id'],'token'=>$v['whatsapp_token'],'version'=>$v['whatsapp_version'],'base_url'=>'https://graph.facebook.com']
  ]);Mail::purge('smtp');Mail::purge('log');
 }
}
