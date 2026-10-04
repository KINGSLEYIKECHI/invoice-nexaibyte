<?php
namespace App\Models;
use Illuminate\Database\Eloquent\{Model, SoftDeletes};
use App\Models\Concerns\BelongsToBusiness;
class Client extends Model
{
    use BelongsToBusiness, SoftDeletes;
    protected $guarded = ['id'];
    public function invoices() { return $this->hasMany(Invoice::class); }
}
