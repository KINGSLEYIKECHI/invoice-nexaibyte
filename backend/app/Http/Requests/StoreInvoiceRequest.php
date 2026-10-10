<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class StoreInvoiceRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return ['po_number'=>'nullable|string|max:100','currency'=>['sometimes',\Illuminate\Validation\Rule::in(app(\App\Services\CurrencyService::class)->codes())],'client_id'=>['required','integer',Rule::exists('clients','id')->where('business_id',$this->user()->business_id)->whereNull('deleted_at')],
        'issue_date'=>'required|date_format:Y-m-d','due_date'=>'required|date_format:Y-m-d|after_or_equal:issue_date','discount_kobo'=>'sometimes|integer|min:0|max:100000000000',
        'tax_percent'=>'required|numeric|min:0|max:100|decimal:0,2','notes'=>'nullable|string|max:5000','terms'=>'nullable|string|max:5000','items'=>'required|array|min:1|max:100',
        'items.*'=>'array:description,quantity,unit_price_kobo,unit','items.*.unit'=>'sometimes|required|string|max:30','items.*.description'=>'required|string|max:255','items.*.quantity'=>'required|numeric|min:0.01|max:100000|decimal:0,2','items.*.unit_price_kobo'=>'required|integer|min:0|max:100000000000'];
    }
}