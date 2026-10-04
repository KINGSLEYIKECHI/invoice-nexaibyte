<?php
namespace App\Http\Middleware;
use Closure;
class PlatformAdmin {public function handle($request,Closure $next){abort_unless($request->user()?->is_platform_admin,403);return $next($request);}}
