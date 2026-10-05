<?php
namespace App\Models;
use App\Models\Concerns\BelongsToBusiness;
class CommercialDocument extends \Illuminate\Database\Eloquent\Model {use BelongsToBusiness;protected $guarded=['id'];protected function casts():array{return ['issue_date'=>'date:Y-m-d','due_date'=>'date:Y-m-d','delivered_at'=>'datetime'];}public function items(){return $this->hasMany(CommercialDocumentItem::class)->orderBy('position');}public function client(){return $this->belongsTo(Client::class)->withTrashed();}public function business(){return $this->belongsTo(Business::class);}}
