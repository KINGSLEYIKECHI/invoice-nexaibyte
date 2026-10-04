<?php
namespace App\Http\Middleware;
use Closure;
use App\Models\ActivityLog;
class TrackActivity {
 public function handle($request,Closure $next){
  try {$response=$next($request);}catch(\Throwable $e){
   if($u=$request->user()){ $status=$e instanceof \Illuminate\Validation\ValidationException?422:($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface?$e->getStatusCode():500);ActivityLog::record($u,$request->method().' '.$request->route()->uri(),$status); }
   throw $e;
  }$u=$request->user();
  if($u&&!\App\Models\User::whereKey($u->id)->exists()){ActivityLog::create(['event'=>$request->method().' '.$request->route()->uri(),'status_code'=>$response->getStatusCode(),'created_at'=>now()]);return $response;}
  if($u){if(!$request->is('api/auth/logout')&&(!$u->last_seen_at||$u->last_seen_at->lt(now()->subMinute())))$u->forceFill(['last_seen_at'=>now()])->save();
   // Route templates only: never record request bodies, headers, query strings, IPs or tokens.
   if($request->method()!=='GET'||$response->getStatusCode()>=400)ActivityLog::record($u,$request->method().' '.$request->route()->uri(),$response->getStatusCode());
  }return $response;
 }
}
