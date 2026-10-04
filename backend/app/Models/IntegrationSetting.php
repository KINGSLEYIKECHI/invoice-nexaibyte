<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class IntegrationSetting extends Model {
 protected $fillable=['id','settings'];
 protected $hidden=['settings'];
 protected function casts():array{return ['settings'=>'encrypted:array'];}
}
