<?php
namespace App\Http\Controllers;
use App\Services\WorkspaceModules;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class WorkspacePreferencesController extends Controller {
 public function show(Request $r,WorkspaceModules $modules){return $modules->preferences($r->user());}
 public function update(Request $r,WorkspaceModules $modules){$ids=array_column($modules->catalog($r->user()),'id');$data=$r->validate(['mode'=>'required|in:simple,advanced,custom','enabled_modules'=>'present|array|max:30','enabled_modules.*'=>['required','string','distinct',Rule::in($ids)]]);$data['enabled_modules']=$data['mode']==='custom'?$data['enabled_modules']:array_values(array_intersect($data['mode']==='simple'?WorkspaceModules::SIMPLE:WorkspaceModules::ADVANCED,$ids));$r->user()->update(['workspace_mode'=>$data['mode'],'enabled_modules'=>array_values($data['enabled_modules'])]);return $modules->preferences($r->user()->fresh());}
}
