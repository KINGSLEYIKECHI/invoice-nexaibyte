<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToBusiness;
class InvoiceItem extends Model
{
    use BelongsToBusiness;
    public $timestamps = false;
    protected $guarded = ['id'];
    protected function casts(): array { return ['quantity'=>'float']; }
}
