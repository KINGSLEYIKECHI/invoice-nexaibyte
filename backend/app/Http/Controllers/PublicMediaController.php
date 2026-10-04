<?php
namespace App\Http\Controllers;
use App\Models\{Business,PlatformSetting};
use Illuminate\Support\Facades\Storage;
class PublicMediaController extends Controller {
 public function business(Business $business){abort_unless($business->logo_path,404);return $this->image($business->logo_path);}
 public function platform(string $kind){abort_unless(in_array($kind,['logo','favicon'],true),404);$asset=PlatformSetting::find(1)?->settings[$kind.'_asset']??null;abort_unless(($asset['driver']??null)==='local',404);return $this->image($asset['path']);}
 private function image(string $path){abort_unless(Storage::disk('public')->exists($path),404);return response()->file(Storage::disk('public')->path($path),['Cache-Control'=>'public, max-age=3600','X-Content-Type-Options'=>'nosniff']);}
}
