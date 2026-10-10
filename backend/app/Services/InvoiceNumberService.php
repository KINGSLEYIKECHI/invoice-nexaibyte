<?php
namespace App\Services;
use App\Models\Business;
use Illuminate\Support\Facades\DB;
class InvoiceNumberService {
    public function next(int $businessId): string {
        return DB::transaction(function() use($businessId) {
            if (DB::connection()->getDriverName()==='sqlite') {
                // SQLite has no row locks: acquire its write lock before reading
                // to avoid a deferred read-to-write lock upgrade under concurrency.
                Business::whereKey($businessId)->update(['next_invoice_number'=>DB::raw('next_invoice_number')]);
            }
            $business=Business::whereKey($businessId)->lockForUpdate()->firstOrFail();
            do {if($business->next_invoice_number>2147483646)throw \Illuminate\Validation\ValidationException::withMessages(['number'=>'Invoice counter limit reached.']);$number=$business->invoice_prefix.$business->invoice_separator.str_pad((string)$business->next_invoice_number,$business->invoice_padding,'0',STR_PAD_LEFT);
            $business->increment('next_invoice_number');}while(\App\Models\Invoice::withoutGlobalScopes()->where('business_id',$businessId)->where('number',$number)->exists());
            return $number;
        },5);
    }
}