<?php
namespace App\Models\Concerns;
use App\Models\Scopes\BusinessScope;
use App\Models\Business;
trait BelongsToBusiness
{
    public static function bootBelongsToBusiness(): void
    {
        static::addGlobalScope(new BusinessScope);
        static::creating(function ($model) {
            if (auth()->check()) $model->business_id = auth()->user()->business_id;
        });
    }
    public function business() { return $this->belongsTo(Business::class); }
}
