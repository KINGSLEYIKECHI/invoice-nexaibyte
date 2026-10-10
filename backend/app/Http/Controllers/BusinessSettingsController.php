<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\{PhoneService,MediaStorage};
class BusinessSettingsController extends Controller {
    public function show(Request $r) { return $r->user()->business->makeVisible(['next_invoice_number','next_quotation_number']); }
    public function update(Request $r) {
        $data=$r->validate(['name'=>'required|string|max:150','email'=>'nullable|email|max:255','phone'=>'nullable|string|max:30','address'=>'nullable|string|max:2000','brand_color'=>['required','regex:/^#[a-fA-F0-9]{6}$/'],'currency'=>['required',\Illuminate\Validation\Rule::in(app(\App\Services\CurrencyService::class)->codes())],'default_tax_percent'=>'required|numeric|between:0,100|decimal:0,2','invoice_prefix'=>'required|alpha_dash|max:15','quotation_prefix'=>'sometimes|required|alpha_dash|max:15','invoice_separator'=>'sometimes|nullable|in:-,/,','quotation_separator'=>'sometimes|nullable|in:-,/,','invoice_padding'=>'sometimes|integer|between:1,10','quotation_padding'=>'sometimes|integer|between:1,10','next_invoice_number'=>'sometimes|integer|between:1,2147483646','next_quotation_number'=>'sometimes|integer|between:1,2147483646','payment_instructions'=>'nullable|string|max:5000','bank_accounts'=>'sometimes|array|max:10','bank_accounts.*'=>'array:bank_name,account_name,account_number,routing_code,details,show_on_invoice,show_on_quotation','bank_accounts.*.bank_name'=>'nullable|string|max:150','bank_accounts.*.account_name'=>'nullable|string|max:150','bank_accounts.*.account_number'=>'nullable|string|max:100','bank_accounts.*.routing_code'=>'nullable|string|max:100','bank_accounts.*.details'=>'nullable|string|max:1000','bank_accounts.*.show_on_invoice'=>'required|boolean','bank_accounts.*.show_on_quotation'=>'required|boolean']);
        $data['phone']=PhoneService::normalize($data['phone']??null);
        return \Illuminate\Support\Facades\DB::transaction(function()use($r,$data){$b=\App\Models\Business::whereKey($r->user()->business_id)->lockForUpdate()->firstOrFail();foreach(['next_invoice_number','next_quotation_number'] as $key)if(isset($data[$key]) && $data[$key]<$b->$key)throw \Illuminate\Validation\ValidationException::withMessages([$key=>'The next number cannot move backwards. Refresh settings for the current counter.']);foreach(['invoice_separator','quotation_separator'] as $key)if(array_key_exists($key,$data))$data[$key]??='';$b->update($data);return $b->makeVisible(['next_invoice_number','next_quotation_number']);},5);
    }
    public function destroy(Request $r) {
        abort_unless($r->user()->role==='owner',403);
        $r->validate(['business_name'=>'required|string']);
        $business=$r->user()->business;
        abort_unless(hash_equals($business->name,$r->business_name),422,'Enter your business name exactly to confirm deletion.');
        \Illuminate\Support\Facades\DB::transaction(function() use($business) {
            $users=$business->users()->get();
            foreach($users as $user) $user->tokens()->delete();
            \App\Models\Invoice::where('business_id',$business->id)->delete();
            \App\Models\CommercialDocument::where('business_id',$business->id)->delete();
            \App\Models\Client::withTrashed()->where('business_id',$business->id)->forceDelete();
            $business->users()->delete();
            $business->delete();
        });
        app(MediaStorage::class)->delete($business->logoAsset());
        return response()->noContent();
    }
    public function logo(Request $r,MediaStorage $media) {
        $r->validate(['logo'=>'required|image|mimes:png,jpg,jpeg|max:1024|dimensions:max_width=4096,max_height=4096']);
        $asset=$media->upload($r->file('logo'),'logos/business-'.$r->user()->business_id);
        try {$result=\Illuminate\Support\Facades\DB::transaction(function()use($r,$asset){
            $b=\App\Models\Business::whereKey($r->user()->business_id)->lockForUpdate()->firstOrFail();$old=$b->logoAsset();
            $b->update(['logo_path'=>$asset['path']??null,'logo_cloud_id'=>$asset['public_id']??null,'logo_cloud_url'=>$asset['url']??null]);
            return [$b,$old];
        });}catch(\Throwable $e){$media->delete($asset);throw $e;}
        $media->delete($result[1]);return $result[0];
    }
}
