<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToBusiness;
class Invoice extends Model
{
    use BelongsToBusiness;
    protected $guarded = ['id'];
    protected $appends = ['balance_kobo', 'public_url'];
    protected function casts(): array { return ['issue_date'=>'date:Y-m-d','due_date'=>'date:Y-m-d','sent_at'=>'datetime','paid_at'=>'datetime','tax_percent'=>'float']; }
    public function client() { return $this->belongsTo(Client::class)->withTrashed(); }
    public function items() { return $this->hasMany(InvoiceItem::class)->orderBy('position'); }
    public function payments() { return $this->hasMany(Payment::class); }
    public function messages() { return $this->hasMany(MessageLog::class); }
    public function getBalanceKoboAttribute(): int { return max(0, $this->total_kobo - $this->amount_paid_kobo); }
    public function getPublicUrlAttribute(): string { return \Illuminate\Support\Facades\URL::signedRoute('public.invoice', ['public_token'=>$this->public_token]); }
    public function recalculateStatus(): void
    {
        if ($this->status === 'void') return;
        $this->amount_paid_kobo = (int) $this->payments()->withoutGlobalScopes()->where('business_id', $this->business_id)->sum('amount_kobo');
        $this->status = $this->amount_paid_kobo >= $this->total_kobo && ($this->amount_paid_kobo > 0 || $this->sent_at)
            ? 'paid' : (($this->sent_at || $this->amount_paid_kobo > 0) && $this->due_date->lt(today())
                ? 'overdue' : ($this->amount_paid_kobo > 0 ? 'partially_paid' : ($this->sent_at ? 'sent' : 'draft')));
        $this->paid_at = $this->status === 'paid' ? ($this->paid_at ?? now()) : null;
        $this->save();
    }
}
