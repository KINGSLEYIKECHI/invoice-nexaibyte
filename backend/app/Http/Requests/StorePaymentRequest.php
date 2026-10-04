<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StorePaymentRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array { return ['amount_kobo'=>'required|integer|min:1|max:1000000000000000','method'=>'required|in:bank_transfer,cash,card,pos,other','reference'=>'nullable|string|max:255','paid_on'=>'required|date_format:Y-m-d|before_or_equal:today','notes'=>'nullable|string|max:5000']; }
}