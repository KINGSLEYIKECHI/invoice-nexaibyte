<?php
namespace App\Http\Middleware;
use Closure;
class EnsureRole {
    public function handle($request,Closure $next,string ...$roles) {
        abort_unless(in_array($request->user()?->role,$roles,true),403,'Your role does not allow this action.');
        return $next($request);
    }
}