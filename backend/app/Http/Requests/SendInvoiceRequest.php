<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class SendInvoiceRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array { return ['channels'=>'required|array|min:1|max:2','channels.*'=>'required|in:email,whatsapp|distinct','message'=>'nullable|string|max:2000']; }
}