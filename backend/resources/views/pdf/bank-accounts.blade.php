@foreach(($business->bank_accounts ?? []) as $account)
@if($account[$visibility] ?? false)
<div style="page-break-inside:avoid"><h3>{{ $account['bank_name'] }}</h3><p style="white-space:pre-line">Account holder: {{ $account['account_name'] }}<br>Account number / IBAN: {{ $account['account_number'] }}@if(!empty($account['routing_code']))<br>SWIFT / routing: {{ $account['routing_code'] }}@endif @if(!empty($account['details']))<br>{{ $account['details'] }}@endif</p></div>
@endif
@endforeach
