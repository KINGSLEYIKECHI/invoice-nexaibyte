@foreach(($business->bank_accounts ?? []) as $account)
@if(($account[$visibility] ?? false) && (!empty($account['bank_name']) || !empty($account['account_name']) || !empty($account['account_number']) || !empty($account['routing_code']) || !empty($account['details'])))
<div style="page-break-inside:avoid">@if(!empty($account['bank_name']))<h3>{{ $account['bank_name'] }}</h3>@endif<p style="white-space:pre-line">@if(!empty($account['account_name']))Account holder: {{ $account['account_name'] }}<br>@endif @if(!empty($account['account_number']))Account number / IBAN: {{ $account['account_number'] }}<br>@endif @if(!empty($account['routing_code']))SWIFT / routing: {{ $account['routing_code'] }}<br>@endif {{ $account['details'] ?? '' }}</p></div>
@endif
@endforeach
