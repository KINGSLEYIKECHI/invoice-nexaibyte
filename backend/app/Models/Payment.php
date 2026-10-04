<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToBusiness;
class Payment extends Model
{
    use BelongsToBusiness;
    protected $guarded = ['id'];
    protected function casts(): array { return ['paid_on'=>'date:Y-m-d']; }
    public function invoice() { return $this->belongsTo(Invoice::class); }
}
