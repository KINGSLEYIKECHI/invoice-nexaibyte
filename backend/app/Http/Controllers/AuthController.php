<?php
namespace App\Http\Controllers;
use App\Models\{Business,User};
use App\Http\Requests\RegisterRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Hash};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
class AuthController extends Controller {
    public function register(RegisterRequest $r) {
        return DB::transaction(function() use($r) {
            $b=Business::create(['name'=>$r->business_name,'slug'=>Str::slug($r->business_name).'-'.Str::lower(Str::random(8)),'email'=>$r->email]);
            $u=User::create(['business_id'=>$b->id,'name'=>$r->name,'email'=>$r->email,'password'=>$r->password,'role'=>'owner']);
            $u->update(['last_login_at'=>now(),'last_seen_at'=>now()]);
            \App\Models\ActivityLog::record($u,'auth.register');
            return response()->json(['token'=>$u->createToken('web')->plainTextToken,'user'=>$u->load('business')],201);
        });
    }
    public function login(Request $r) {
        $r->validate(['email'=>'required|email','password'=>'required|string']);
        $u=User::where('email',$r->email)->first();
        if(!$u || !Hash::check($r->password,$u->password)) throw ValidationException::withMessages(['email'=>'These credentials do not match our records.']);
        $u->update(['last_login_at'=>now(),'last_seen_at'=>now()]);
        \App\Models\ActivityLog::record($u,'auth.login');
        return ['token'=>$u->createToken('web')->plainTextToken,'user'=>$u->load('business')];
    }
    public function me(Request $r) { return $r->user()->load('business'); }
    public function logout(Request $r) { $r->user()->currentAccessToken()->delete(); $r->user()->forceFill(['last_seen_at'=>null])->save(); return response()->noContent(); }
}