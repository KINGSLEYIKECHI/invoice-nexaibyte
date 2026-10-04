<?php
namespace App\Http\Middleware;
use Closure;
use App\Services\IntegrationSettings;
class RuntimeIntegrations {public function handle($request,Closure $next){app(IntegrationSettings::class)->apply();return $next($request);}}
