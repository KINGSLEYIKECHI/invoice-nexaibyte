<h2>{{ $invoice->business->name }}</h2>
<p>Hello {{ $invoice->client->name }},</p>
<p>{{ $messageText ?: 'Please find your invoice attached.' }}</p>
<p>Invoice <strong>{{ $invoice->number }}</strong> · Balance NGN {{ number_format($invoice->balance_kobo/100,2) }} · Due {{ $invoice->due_date->format('d M Y') }}</p>
<p><a href="{{ $invoice->public_url }}">View and download your invoice</a></p>
<p>{{ $invoice->business->payment_instructions }}</p>
<p>Thank you for your business.</p>