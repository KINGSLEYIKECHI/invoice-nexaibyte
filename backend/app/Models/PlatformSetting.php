<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PlatformSetting extends Model {
 protected $fillable=['id','settings'];
 protected function casts(): array {return ['settings'=>'array'];}
 public static function defaults(): array {return [
 'product_name'=>'Ledger','company_name'=>'Nexaibyte LTD','company_url'=>'https://nexaibyte.com','support_email'=>'hello@nexaibyte.com',
 'primary_color'=>'#0C7062','background_color'=>'#123D32','accent_color'=>'#B9CF9C','logo_url'=>'','favicon_url'=>'',
 'tagline'=>'A little order. A lot of possibility.','hero_title'=>'Good business starts with clear numbers.',
 'hero_description'=>'Send beautiful invoices, keep payments in view, and get back to the work you love.',
 'login_title'=>'A fresh view of your business.','login_description'=>'Sign in to your workspace to get started.',
 'register_title'=>'Make room for growth.','register_description'=>'Create your free workspace. Keep every invoice in order.',
 'announcement'=>''];}
 public static function current(): array {return array_replace(self::defaults(),array_intersect_key(self::find(1)?->settings ?? [],self::defaults()));}
}

