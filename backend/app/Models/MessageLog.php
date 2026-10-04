<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToBusiness;
class MessageLog extends Model
{
    use BelongsToBusiness;
    protected $guarded = ['id'];
    public function invoice() { return $this->belongsTo(Invoice::class); }
}
