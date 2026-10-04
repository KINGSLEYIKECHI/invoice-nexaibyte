<?php
namespace App\Http\Controllers;
use App\Models\PlatformSetting;
use Illuminate\Http\Request;
class PlatformSettingsController extends Controller {
 public function show(){return PlatformSetting::current();}
 public function upload(Request $r,string $kind,\App\Services\MediaStorage $media){
  abort_unless($r->user()->is_platform_admin,403);abort_unless(in_array($kind,['logo','favicon'],true),404);
  $r->validate(['image'=>'required|image|mimes:png,jpg,jpeg|max:1024|dimensions:max_width=4096,max_height=4096']);
  $asset=$media->upload($r->file('image'),'branding/'.$kind,$kind==='favicon');
  try {$old=\Illuminate\Support\Facades\DB::transaction(function()use($asset,$kind){
   PlatformSetting::firstOrCreate(['id'=>1],['settings'=>PlatformSetting::defaults()]);
   $record=PlatformSetting::whereKey(1)->lockForUpdate()->firstOrFail();$settings=$record->settings;$old=$settings[$kind.'_asset']??null;
   $settings[$kind.'_asset']=$asset;$settings[$kind.'_url']=$asset['url']??url('media/platform/'.$kind).'?v='.substr(hash('sha256',$asset['path']),0,16);
   $record->update(['settings'=>$settings]);return $old;
  });}catch(\Throwable $e){$media->delete($asset);throw $e;}
  $media->delete($old);return PlatformSetting::current();
 }
 public function update(Request $r){
  abort_unless($r->user()->is_platform_admin,403);
  $rules=[];
  foreach(['product_name','company_name','tagline','hero_title','login_title','register_title'] as $key)$rules[$key]='required|string|max:150';
  foreach(['hero_description','login_description','register_description'] as $key)$rules[$key]='required|string|max:500';
  $rules['announcement']='nullable|string|max:500';
  $rules['support_email']='required|email|max:255';
  $rules['company_url']='required|url:http,https|max:500';
  // Image URLs are changed only through the validated upload endpoints.

  foreach(['primary_color','background_color','accent_color'] as $key)$rules[$key]=['required','regex:/^#[0-9a-fA-F]{6}$/'];
  $data=$r->validate($rules);
  // Only explicitly validated public presentation settings can be changed.
  \Illuminate\Support\Facades\DB::transaction(function()use($data){
   PlatformSetting::firstOrCreate(['id'=>1],['settings'=>PlatformSetting::defaults()]);
   $record=PlatformSetting::whereKey(1)->lockForUpdate()->firstOrFail();
   $record->update(['settings'=>array_replace($record->settings,$data)]);
  });
  return PlatformSetting::current();
 }
}
