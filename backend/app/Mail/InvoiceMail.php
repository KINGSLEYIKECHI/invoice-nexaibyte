<?php
namespace App\Mail;
use App\Models\Invoice;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\{Content,Envelope,Attachment};
class InvoiceMail extends Mailable {
    public function __construct(public Invoice $invoice,public string $pdf,public ?string $messageText=null) {}
    public function envelope(): Envelope { return new Envelope(subject:'Invoice '.$this->invoice->number.' from '.$this->invoice->business->name); }
    public function content(): Content { return new Content(view:'emails.invoice'); }
    public function attachments(): array { return [Attachment::fromData(fn()=>$this->pdf,$this->invoice->number.'.pdf')->withMime('application/pdf')]; }
}