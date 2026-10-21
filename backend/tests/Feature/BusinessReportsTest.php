<?php
use App\Models\{Invoice,Payment,Client};
beforeEach(function(){$this->seed();$this->actingAs(demoUser());});
it('separates opening period activity and closing balance with currency precision',function(){
 $client=Client::first();$i=$this->postJson('/api/invoices',array_replace(invoiceBody($client->id),['issue_date'=>'2026-01-01','due_date'=>'2026-01-15','tax_percent'=>0,'currency'=>'JPY','items'=>[['description'=>'Work','quantity'=>1,'unit_price_kobo'=>10000]]]))->assertCreated()->json();$invoice=Invoice::findOrFail($i['id']);$invoice->update(['status'=>'sent']);$invoice->payments()->create(['amount_kobo'=>2000,'recorded_by'=>demoUser()->id,'method'=>'bank_transfer','paid_on'=>'2026-01-20']);$invoice->payments()->create(['amount_kobo'=>1000,'recorded_by'=>demoUser()->id,'method'=>'bank_transfer','paid_on'=>'2026-02-10']);$invoice->payments()->create(['amount_kobo'=>3000,'recorded_by'=>demoUser()->id,'method'=>'bank_transfer','paid_on'=>'2026-03-01']);
 $r=$this->getJson('/api/reports/statement?client_id='.$client->id.'&currency=JPY&from=2026-02-01&to=2026-02-28')->assertOk()->assertJsonPath('totals.0.minor_units',0)->assertJsonPath('totals.0.opening_minor',8000)->assertJsonPath('totals.0.paid_minor',1000)->assertJsonPath('totals.0.closing_minor',7000)->assertJsonCount(1,'rows')->assertJsonPath('rows.0.balance_minor',7000);
 $this->getJson('/api/reports/ageing?currency=JPY&to=2026-02-28')->assertOk()->assertJsonPath('totals.0.days_31_60_minor',7000)->assertJsonPath('rows.0.days_overdue',44);
});
it('never exposes another business report or accepts a foreign client',function(){
 $client=Client::first();$this->actingAs(demoUser('owner@kemtech.test'));$this->getJson('/api/reports/statement?client_id='.$client->id)->assertUnprocessable();$r=$this->getJson('/api/reports/ageing')->assertOk();foreach($r->json('rows') as $row)expect($row['client'])->not->toBe($client->name);
});
it('validates filters and exports exact minor units without spreadsheet formulas',function(){
 $this->getJson('/api/reports/statement')->assertUnprocessable();$this->getJson('/api/reports/ageing?currency=BAD')->assertUnprocessable();$this->getJson('/api/reports/statement?client_id='.Client::first()->id.'&from=2026-03-01&to=2026-01-01')->assertUnprocessable();
 $i=Invoice::where('status','sent')->first();$i->client->update(['name'=>'=FORMULA()']);$r=$this->get('/api/reports/ageing?format=csv&to=2099-12-31')->assertOk()->assertHeader('Content-Type','text/csv; charset=UTF-8');expect($r->streamedContent())->toContain("'=FORMULA()")->toContain('Balance minor');
});

it('keeps currencies separate and rejects aggregates that lose safe integer precision',function(){
 $client=Client::first();foreach(['USD','JPY'] as $currency){$i=$this->postJson('/api/invoices',array_replace(invoiceBody($client->id),['currency'=>$currency,'issue_date'=>'2026-01-01','due_date'=>'2026-01-31','tax_percent'=>0]))->assertCreated()->json();Invoice::find($i['id'])->update(['status'=>'sent']);}
 $r=$this->getJson('/api/reports/statement?client_id='.$client->id.'&from=2026-01-01&to=2026-02-28')->assertOk();expect(array_column($r->json('totals'),'currency'))->toBe(['USD','JPY']);
 foreach(range(1,2) as $n){$i=Invoice::where('currency','USD')->first()->replicate(['number','public_token']);$i->number='LARGE-'.$n;$i->public_token=(string)\Illuminate\Support\Str::uuid();$i->total_kobo=5000000000000000;$i->save();}
 $this->getJson('/api/reports/statement?client_id='.$client->id.'&currency=USD&from=2026-01-01&to=2026-02-28')->assertUnprocessable();
});

it('handles blank optional dates and starts a default statement in the selected end month',function(){
 $this->getJson('/api/reports/ageing?from=&to=')->assertOk()->assertJsonPath('from',null)->assertJsonPath('to',today()->format('Y-m-d'));
 $this->getJson('/api/reports/statement?client_id='.Client::first()->id.'&to=2026-02-28')->assertOk()->assertJsonPath('from','2026-02-01');
});

it('includes pre-invoice payments as client credit without counting future invoice revenue',function(){
 $client=Client::first();$i=$this->postJson('/api/invoices',array_replace(invoiceBody($client->id),['currency'=>'JPY','issue_date'=>'2026-03-01','due_date'=>'2026-03-20','tax_percent'=>0]))->assertCreated()->json();$invoice=Invoice::find($i['id']);$invoice->update(['status'=>'sent']);$invoice->payments()->create(['amount_kobo'=>1000,'method'=>'bank_transfer','recorded_by'=>demoUser()->id,'paid_on'=>'2026-02-10']);
 $this->getJson('/api/reports/statement?client_id='.$client->id.'&currency=JPY&from=2026-02-01&to=2026-02-28')->assertOk()->assertJsonPath('totals.0.invoiced_minor',0)->assertJsonPath('totals.0.paid_minor',1000)->assertJsonPath('totals.0.closing_minor',-1000)->assertJsonCount(1,'rows');
 $this->getJson('/api/reports/ageing?currency=JPY&to=2026-02-28')->assertOk()->assertJsonCount(0,'rows');
});
