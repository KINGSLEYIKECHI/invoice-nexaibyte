<?php
namespace App\Models;
use App\Models\Concerns\BelongsToBusiness;
class CommercialDocumentItem extends \Illuminate\Database\Eloquent\Model {use BelongsToBusiness;public $timestamps=false;protected $guarded=['id'];}
