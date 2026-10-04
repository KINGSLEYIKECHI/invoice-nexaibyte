<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
class User extends Authenticatable {
    use HasApiTokens, HasFactory, Notifiable;
    protected $attributes = ['is_platform_admin'=>false];
    protected $guarded = ['id','is_platform_admin'];
    protected $hidden = ['password','remember_token'];
    protected function casts(): array { return ['is_platform_admin'=>'boolean','password'=>'hashed','last_login_at'=>'datetime']; }
    public function business() { return $this->belongsTo(Business::class); }
    public function isManager(): bool { return in_array($this->role,['owner','admin'],true); }
}
