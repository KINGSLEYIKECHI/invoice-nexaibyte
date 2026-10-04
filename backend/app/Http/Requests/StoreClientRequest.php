<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreClientRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array { return ['name'=>'required|string|max:150','email'=>'nullable|email|max:255','phone'=>'nullable|string|max:30','company'=>'nullable|string|max:150','address'=>'nullable|string|max:2000','notes'=>'nullable|string|max:5000']; }
}