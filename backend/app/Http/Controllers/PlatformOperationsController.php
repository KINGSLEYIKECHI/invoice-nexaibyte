<?php
namespace App\Http\Controllers;
use App\Models\{User,Business,ActivityLog};
use App\Services\IntegrationSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Http,Mail};
use Illuminate\Validation\Rule;
use Carbon\CarbonImmutable;
class PlatformOperationsController extends Controller {
 public function integrations(IntegrationSettings $settings){return $settings->safe();}
 public function saveIntegrations(Request $r,IntegrationSettings $settings){
  $rules=[
   'mail_driver'=>['required',Rule::in(['smtp','log'])],'smtp_host'=>'required|string|max:255|regex:/^[a-zA-Z0-9.-]+$/','smtp_port'=>'required|integer|between:1,65535','smtp_security'=>['required',Rule::in(['tls','ssl'])],'smtp_username'=>'nullable|string|max:255','mail_from_address'=>'required|email|max:255','mail_from_name'=>'required|string|max:150',
   'media_driver'=>['required',Rule::in(['local','cloudinary'])],'cloudinary_cloud_name'=>'nullable|string|max:100|regex:/^[a-zA-Z0-9_-]+$/','cloudinary_folder'=>'required|string|max:100|regex:/^[a-zA-Z0-9_\/-]+$/',
   'whatsapp_enabled'=>'required|boolean','whatsapp_phone_id'=>'nullable|string|max:30|regex:/^[0-9]+$/','whatsapp_version'=>'required|string|regex:/^v[0-9]{1,2}\.0$/','payment_owner_email'=>'required|boolean','payment_client_email'=>'required|boolean'
  ];foreach(IntegrationSettings::SECRETS as $key){$rules[$key]='nullable|string|max:4096';$rules['clear_'.$key]='sometimes|boolean';}
  $data=$r->validate($rules);foreach($data as $k=>$v)if($v===null)$data[$k]='';
  $settings->save($data);$settings->apply();return $settings->safe();
 }
 public function testIntegration(Request $r,string $kind,IntegrationSettings $settings){
  $v=$settings->values();$settings->apply();
  if($kind==='smtp'){$r->validate(['recipient'=>'required|email|max:255']);if($v['mail_driver']!=='smtp')return response()->json(['message'=>'Log mode does not send email. Select SMTP, save, then test.'],422);}
  try {
   if($kind==='smtp'){Mail::raw('Your Invoice SaaS SMTP settings accepted this test. Confirm receipt in your inbox.',fn($m)=>$m->to($r->recipient)->subject('Invoice SaaS SMTP test'));return ['message'=>'SMTP server accepted the test email. Check the recipient inbox and spam folder.'];}
   if($kind==='cloudinary'){
    abort_unless($v['cloudinary_cloud_name']&&$v['cloudinary_api_key']&&$v['cloudinary_api_secret'],422,'Save all Cloudinary credentials first.');
    Http::withBasicAuth($v['cloudinary_api_key'],$v['cloudinary_api_secret'])->withoutRedirecting()->connectTimeout(5)->timeout(15)->get('https://api.cloudinary.com/v1_1/'.$v['cloudinary_cloud_name'].'/usage')->throw();
   }elseif($kind==='whatsapp'){
    abort_unless($v['whatsapp_phone_id']&&$v['whatsapp_token'],422,'Save the WhatsApp phone number ID and token first.');
    Http::withToken($v['whatsapp_token'])->withoutRedirecting()->connectTimeout(5)->timeout(15)->get('https://graph.facebook.com/'.$v['whatsapp_version'].'/'.$v['whatsapp_phone_id'],['fields'=>'id,display_phone_number'])->throw();
   }else abort(404);
   return ['message'=>'Provider authenticated successfully. This check does not upload images or send WhatsApp messages.'];
  }catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){throw $e;}
  catch(\Throwable $e){return response()->json(['message'=>'Connection test failed. Check credentials, provider permissions, quota, TLS and host connectivity. No provider error or secret is exposed.'],422);}
 }
 public function overview(Request $r){
  $r->validate(['days'=>'sometimes|integer|between:1,90']);
  $days=$r->integer('days',30);$start=CarbonImmutable::now('Africa/Lagos')->startOfDay()->subDays($days-1);$end=$start->addDays($days);
  // Aggregate in SQL so usage reports do not load every historical event into PHP.
  // Lagos is UTC+01:00; numeric offsets do not require MySQL timezone tables.
  $dateSql=\Illuminate\Support\Facades\DB::getDriverName()==='sqlite'?"DATE(created_at, '+1 hour')":"DATE(CONVERT_TZ(created_at, '+00:00', '+01:00'))";
  $logins=ActivityLog::whereIn('event',['auth.login','auth.register'])->where('created_at','>=',$start->utc())->where('created_at','<',$end->utc())->selectRaw($dateSql.' AS day, COUNT(DISTINCT user_id) AS total')->groupBy('day')->pluck('total','day');
  $registrations=User::where('created_at','>=',$start->utc())->where('created_at','<',$end->utc())->selectRaw($dateSql.' AS day, COUNT(*) AS total')->groupBy('day')->pluck('total','day');$daily=[];
  for($n=0;$n<$days;$n++){$date=$start->addDays($n)->format('Y-m-d');$daily[]=['date'=>$date,'logged_in_users'=>(int)($logins[$date]??0),'new_users'=>(int)($registrations[$date]??0)];}
  return ['users'=>User::count(),'businesses'=>Business::count(),'active_last_5_minutes'=>User::where('last_seen_at','>=',now()->subMinutes(5))->count(),'timezone'=>'Africa/Lagos','daily'=>$daily];
 }
 public function users(Request $r){$r->validate(['search'=>'nullable|string|max:100','page'=>'sometimes|integer|min:1']);$q=User::with('business:id,name')->select(['id','business_id','name','email','role','is_platform_admin','created_at','last_login_at','last_seen_at']);if($term=$r->string('search')->toString())$q->where(fn($q)=>$q->where('name','like','%'.$term.'%')->orWhere('email','like','%'.$term.'%'));return $q->latest('id')->paginate(25);}
 public function deliveries(Request $r){return \App\Models\PaymentNotification::select(['id','business_id','payment_id','recipient','kind','status','attempts','error','created_at','sent_at'])->latest('id')->paginate(25);}
 public function retryDelivery(int $id){$n=\App\Models\PaymentNotification::findOrFail($id);abort_unless($n->status==='failed',422,'Only failed deliveries can be retried.');$n->update(['status'=>'pending','error'=>null]);\App\Jobs\SendPaymentEmail::dispatch($n->id,$n->business_id);return ['message'=>'Payment email queued for another attempt.'];}
 public function activity(Request $r){$data=$r->validate(['user_id'=>'sometimes|integer|min:1','page'=>'sometimes|integer|min:1','event'=>'nullable|string|max:160']);$q=ActivityLog::with('user:id,name,email');if(isset($data['user_id']))$q->where('user_id',$data['user_id']);if(!empty($data['event']))$q->where('event',$data['event']);return $q->latest('id')->paginate(50);}
}
