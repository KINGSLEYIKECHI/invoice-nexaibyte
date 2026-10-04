<?php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', api: __DIR__.'/../routes/api.php', commands: __DIR__.'/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(prepend:[\App\Http\Middleware\RuntimeIntegrations::class]);
        $middleware->api(prepend:[\App\Http\Middleware\RuntimeIntegrations::class]);
        $middleware->alias(['platform.admin'=>\App\Http\Middleware\PlatformAdmin::class,'activity'=>\App\Http\Middleware\TrackActivity::class,'tenant'=>\App\Http\Middleware\SetTenant::class,'role'=>\App\Http\Middleware\EnsureRole::class]);
        $middleware->priority([ \Illuminate\Http\Middleware\HandleCors::class, \Illuminate\Auth\Middleware\Authenticate::class, \App\Http\Middleware\SetTenant::class, \Illuminate\Routing\Middleware\SubstituteBindings::class ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(fn($request,$e)=>$request->is('api/*') || $request->expectsJson());
    })->create();