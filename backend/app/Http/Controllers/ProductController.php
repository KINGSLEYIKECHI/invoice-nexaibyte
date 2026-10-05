<?php
namespace App\Http\Controllers;
use App\Models\Product;
use App\Services\{CurrencyService,ProductImportService};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class ProductController extends Controller {
 private function data(Request $r,?Product $product=null):array {
  if(is_string($r->input('sku')))$r->merge(['sku'=>strtoupper(trim($r->input('sku')))]);$d=$r->validate(['sku'=>['required','string','max:80','regex:/^[A-Za-z0-9][A-Za-z0-9._-]*$/',Rule::unique('products')->where('business_id',$r->user()->business_id)->ignore($product?->id)],'name'=>'required|string|max:150','description'=>'nullable|string|max:2000','unit'=>'required|string|max:30','unit_price'=>'required|string|max:30','currency'=>['required',Rule::in(app(CurrencyService::class)->codes())],'is_active'=>'required|boolean']);$d['unit_price_minor']=app(CurrencyService::class)->minor($d['unit_price'],$d['currency']);unset($d['unit_price']);return $d;
 }
 public function index(Request $r){$r->validate(['search'=>'nullable|string|max:100','currency'=>'nullable|string|size:3','active'=>'sometimes|boolean','per_page'=>'sometimes|integer|between:1,100']);$q=Product::query();if($r->filled('search'))$q->where(fn($q)=>$q->where('name','like','%'.$r->search.'%')->orWhere('sku','like','%'.$r->search.'%'));if($r->filled('currency'))$q->where('currency',$r->currency);if($r->has('active'))$q->where('is_active',$r->boolean('active'));return $q->orderBy('name')->paginate($r->integer('per_page',20));}
 public function show(Product $product){return $product;}
 public function store(Request $r){return response()->json(Product::create($this->data($r)),201);}
 public function update(Request $r,Product $product){$product->update($this->data($r,$product));return $product;}
 public function template(Request $r,string $format,ProductImportService $service){[$bytes,$type]=$service->template($format,$r->user()->business->currency);return response($bytes,200,['Content-Type'=>$type,'Content-Disposition'=>'attachment; filename="products-template.'.$format.'"']);}
 public function import(Request $r,ProductImportService $service){$r->validate(['file'=>'required|file|max:2048','preview'=>'required|boolean','update_existing'=>'sometimes|boolean']);return $r->boolean('preview')?$service->preview($r->file('file'),$r->boolean('update_existing')):$service->import($r->file('file'),$r->boolean('update_existing'));}
}
