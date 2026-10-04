<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Business extends Model
{
    protected $guarded = ['id'];
    protected $hidden = ['next_invoice_number','logo_cloud_id','logo_cloud_url'];
    protected function casts(): array { return ['default_tax_percent' => 'float']; }
    public function users() { return $this->hasMany(User::class); }
    public function getLogoUrlAttribute() { return $this->logo_cloud_url ?: ($this->logo_path ? url('media/businesses/'.$this->id.'/logo').'?v='.substr(hash('sha256',$this->logo_path),0,16) : null); }
    public function logoAsset(): ?array {return $this->logo_cloud_id ? ['driver'=>'cloudinary','public_id'=>$this->logo_cloud_id,'url'=>$this->logo_cloud_url] : ($this->logo_path ? ['driver'=>'local','path'=>$this->logo_path] : null);}
    protected $appends = ['logo_url'];
}
