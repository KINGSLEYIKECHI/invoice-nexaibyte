<?php
namespace App\Models;
use App\Models\Concerns\BelongsToBusiness;
class Product extends \Illuminate\Database\Eloquent\Model {use BelongsToBusiness;protected $guarded=['id'];protected function casts():array{return ['is_active'=>'boolean','unit_price_minor'=>'integer'];}}
