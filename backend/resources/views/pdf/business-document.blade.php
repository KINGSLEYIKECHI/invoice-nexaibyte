<!doctype html><html><head><meta charset="utf-8"><style>
@page{margin:24mm 20mm}body{font-family:DejaVu Sans,sans-serif;font-size:10pt;line-height:1.5;color:#172c32}h1{font-size:20pt;color:#000;margin:0 0 18px}p{white-space:pre-wrap;overflow-wrap:break-word;margin:0 0 5px}table{width:100%;border-collapse:collapse;table-layout:fixed;font-size:8pt;margin-top:16px}th,td{padding:6px 4px;border-bottom:1px solid #dae1df;text-align:right}th:first-child,td:first-child{text-align:left;width:32%}td,th{word-wrap:break-word}thead{display:table-header-group}tr{page-break-inside:avoid}</style></head><body><h1>{{ $content['title'] }}</h1>
@foreach(explode("\n",str_replace("\r",'', $content['body'])) as $line)<p>{{ $line ?: ' ' }}</p>@endforeach
@if($forecast)<p>Currency {{ $forecast['currency'] }} | Annual increase {{ $content['salary']['increase'] }}%</p>
@foreach(array_chunk(array_keys($forecast['totals']),4) as $years)
<table><thead><tr><th>Role</th>@foreach($years as $y)<th>Year {{ $y+1 }}</th>@endforeach</tr></thead><tbody>@foreach($forecast['rows'] as $row)<tr><td>{{ $row['role'] }}</td>@foreach($years as $y)<td>{{ number_format($row['values'][$y]/(10**$forecast['digits']),$forecast['digits']) }}</td>@endforeach</tr>@endforeach<tr><th>Total</th>@foreach($years as $y)<th>{{ number_format($forecast['totals'][$y]/(10**$forecast['digits']),$forecast['digits']) }}</th>@endforeach</tr></tbody></table>
@endforeach @endif
</body></html>
