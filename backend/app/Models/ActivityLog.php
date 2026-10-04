<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ActivityLog extends Model {
 public $timestamps=false;
 protected $guarded=['id'];
 protected function casts():array{return ['created_at'=>'datetime'];}
 public function user(){return $this->belongsTo(User::class);}
 public static function record(\App\Models\User $user,string $event,?int $status=null):void {
  static::create(['user_id'=>$user->id,'business_id'=>$user->business_id,'event'=>$event,'status_code'=>$status,'created_at'=>now()]);
 }
}
