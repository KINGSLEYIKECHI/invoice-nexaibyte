<h2>{{ $invoice->business->name }}</h2>
<p>Hello {{ $invoice->client->name }},</p>
<p>{{ $messageText ?: 'Please find your invoice attached.' }}</p>
<p>Invoice <strong>{{ $invoice->number }}</strong> · Balance {{ app(\App\Services\CurrencyService::class)->format($invoice->balance_kobo,$invoice->currency,$invoice->currency_minor_units) }} · Due {{ $invoice->due_date->format('d M Y') }}</p>
<p><a href="{{ $invoice->public_url }}">View and download your invoice</a></p>
<p>{{ $invoice->business->payment_instructions }}</p>
<p>Thank you for your business.</p>