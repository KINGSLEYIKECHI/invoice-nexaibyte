<?php
namespace App\Services;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Http,Storage,Log};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
class MediaStorage {
 public function upload(UploadedFile $file,string $folder,bool $favicon=false):array {
  $image=imagecreatefromstring(file_get_contents($file->getRealPath()));
  if(!$image)throw ValidationException::withMessages(['image'=>'Unable to decode the image.']);
  $w=imagesx($image);$h=imagesy($image);
  if($favicon&&$w!==$h){imagedestroy($image);throw ValidationException::withMessages(['image'=>'Use a square PNG or JPG for the favicon.']);}
  $scale=min(1,1024/max($w,$h));$width=$favicon?64:max(1,(int)round($w*$scale));$height=$favicon?64:max(1,(int)round($h*$scale));
  $output=imagecreatetruecolor($width,$height);imagealphablending($output,false);imagesavealpha($output,true);
  imagefill($output,0,0,imagecolorallocatealpha($output,0,0,0,127));imagecopyresampled($output,$image,0,0,0,0,$width,$height,$w,$h);
  $temp=tempnam(sys_get_temp_dir(),'invoice-image-');
  try {imagepng($output,$temp);$bytes=file_get_contents($temp);
   if(strlen($bytes)>2*1024*1024)throw ValidationException::withMessages(['image'=>'The optimized image is too large. Use a simpler image.']);
   if(config('media.driver')==='local'){
    $path=$folder.'/'.Str::uuid().'.png';if(!Storage::disk('public')->put($path,$bytes))throw new \RuntimeException('Unable to store image');
    return ['driver'=>'local','path'=>$path];
   }
   if(config('media.driver')!=='cloudinary')throw ValidationException::withMessages(['image'=>'Unsupported image storage configuration.']);
   $cloud=config('media.cloud_name');$key=config('media.api_key');$secret=config('media.api_secret');
   if(!$cloud||!preg_match('/^[a-zA-Z0-9_-]+$/',$cloud)||!$key||!$secret)throw ValidationException::withMessages(['image'=>'Cloud image storage is not configured.']);
   $publicId=trim(config('media.folder','invoice-saas'),'/').'/'.$folder.'/'.Str::uuid();
   try {$response=Http::withBasicAuth($key,$secret)->connectTimeout(5)->timeout(30)->attach('file',$bytes,'image.png')->post('https://api.cloudinary.com/v1_1/'.$cloud.'/image/upload',['public_id'=>$publicId,'overwrite'=>'false']);}
   catch(\Throwable $e){throw ValidationException::withMessages(['image'=>'Cloud storage is unavailable. Please retry.']);}
   $data=$response->json();
   if(!$response->successful()||($data['public_id']??null)!==$publicId||!$this->trustedUrl($data['secure_url']??''))throw ValidationException::withMessages(['image'=>'Cloud upload failed. Check the account credentials and quota.']);
   return ['driver'=>'cloudinary','public_id'=>$publicId,'url'=>$data['secure_url']];
  }finally {imagedestroy($image);imagedestroy($output);if(is_file($temp))unlink($temp);}
 }
 public function trustedUrl(string $url):bool {
  $parts=parse_url($url);return ($parts['scheme']??'')==='https'&&($parts['host']??'')==='res.cloudinary.com'&&!isset($parts['user'])&&!isset($parts['pass'])&&!isset($parts['port'])&&str_starts_with($parts['path']??'','/'.config('media.cloud_name').'/image/upload/');
 }
 public function delete(?array $asset):void {
  if(!$asset)return;
  try {
   if(($asset['driver']??'')==='local'){Storage::disk('public')->delete($asset['path']);return;}
   if(($asset['driver']??'')!=='cloudinary'||!$this->trustedUrl($asset['url']??''))return;
   $r=Http::withBasicAuth(config('media.api_key'),config('media.api_secret'))->connectTimeout(3)->timeout(10)->asForm()->post('https://api.cloudinary.com/v1_1/'.config('media.cloud_name').'/image/destroy',['public_id'=>$asset['public_id'],'invalidate'=>'true']);
   if(!$r->successful())Log::warning('Old cloud image cleanup failed; remove unused assets in the provider console.');
  }catch(\Throwable $e){Log::warning('Old image cleanup failed; remove unused assets in the provider console.');}
 }
 public function dataUri(?array $asset):?string {
  if(!$asset)return null;
  if(($asset['driver']??'')==='local'){
   if(!Storage::disk('public')->exists($asset['path']))return null;
   $bytes=Storage::disk('public')->get($asset['path']);
  }else {
   if(!$this->trustedUrl($asset['url']??''))return null;
   try {
    $r=Http::connectTimeout(3)->timeout(8)->withOptions(['allow_redirects'=>false,'progress'=>function($total,$downloaded){if($total>2*1024*1024||$downloaded>2*1024*1024)throw new \RuntimeException('Cloud image exceeds PDF limit');}])->get($asset['url']);
    if(!$r->successful()||strlen($r->body())>2*1024*1024)return null;$bytes=$r->body();
   }catch(\Throwable $e){Log::warning('Cloud logo unavailable while rendering an invoice.');return null;}
  }
  $info=@getimagesizefromstring($bytes);if(!$info||!in_array($info['mime'],['image/png','image/jpeg'],true))return null;
  return 'data:'.$info['mime'].';base64,'.base64_encode($bytes);
 }
}
