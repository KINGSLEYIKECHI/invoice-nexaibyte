<?php
namespace App\Http\Middleware;
use Closure;
class SetTenant {
    public function handle($request, Closure $next) {
        abort_unless($request->user()?->business_id,403,'No business assigned.');
        return $next($request);
    }
}