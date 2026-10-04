<?php
namespace App\Models\Scopes;
use Illuminate\Database\Eloquent\{Builder, Model, Scope};
class BusinessScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // Fail closed: background/public operations explicitly bypass and constrain the scope.
        $builder->where($model->qualifyColumn('business_id'), auth()->user()?->business_id ?? 0);
    }
}
