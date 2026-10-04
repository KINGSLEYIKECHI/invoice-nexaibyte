<?php
namespace App\Http\Controllers;
use App\Models\Client;
use App\Http\Requests\StoreClientRequest;
use App\Services\PhoneService;
use Illuminate\Http\Request;
class ClientController extends Controller {
    public function index(Request $r) {
        $q=Client::query();
        if($s=$r->query('search')) $q->where(fn($q)=>$q->where('name','like',"%$s%")->orWhere('company','like',"%$s%")->orWhere('email','like',"%$s%"));
        return $q->orderBy('name')->paginate(min(max((int)$r->query('per_page',20),1),100));
    }
    public function show(Client $client) { return $client; }
    public function store(StoreClientRequest $r) { $data=$r->validated(); $data['phone']=PhoneService::normalize($data['phone']??null); return response()->json(Client::create($data),201); }
    public function update(StoreClientRequest $r,Client $client) { $data=$r->validated(); $data['phone']=PhoneService::normalize($data['phone']??null); $client->update($data); return $client; }
    public function destroy(Client $client) { $this->authorize('delete',$client); $client->delete(); return response()->noContent(); }
}