<?php
namespace App\Policies;
use App\Models\{User,Invoice};
class InvoicePolicy {
    public function void(User $user,Invoice $invoice): bool { return $user->business_id===$invoice->business_id && $user->isManager(); }
}