<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
class TeamController extends Controller {
    public function index(Request $r) { return $r->user()->business->users()->where('email','not like','%@disabled.invalid')->orderBy('name')->get(); }
    public function store(Request $r) {
        $data=$r->validate(['name'=>'required|string|max:100','email'=>'required|email|unique:users|max:255','password'=>'required|string|min:8','role'=>'required|in:admin,staff']);
        return response()->json(User::create(array_merge($data,['business_id'=>$r->user()->business_id])),201);
    }
    private function target(string $id): User { return User::where('business_id',auth()->user()->business_id)->findOrFail($id); }
    public function update(Request $r,string $user) {
        $target=$this->target($user); $this->authorize('manage',$target); $data=$r->validate(['role'=>'required|in:admin,staff']); $target->update($data); return $target;
    }
    public function destroy(string $user) {
        $target=$this->target($user); $this->authorize('manage',$target);
        // Preserve invoice/payment audit foreign keys while removing access.
        $target->tokens()->delete(); $target->update(['password'=>bin2hex(random_bytes(32)),'role'=>'staff','email'=>'removed-'.$target->id.'-'.bin2hex(random_bytes(4)).'@disabled.invalid','name'=>'Removed member']);
        return response()->noContent();
    }
}