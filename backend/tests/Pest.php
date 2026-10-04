<?php
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
uses(TestCase::class,RefreshDatabase::class)->in('Feature');
function demoUser(string $email='owner@brightspark.test') { return \App\Models\User::where('email',$email)->firstOrFail(); }
function invoiceBody(int $client): array { return ['client_id'=>$client,'issue_date'=>today()->format('Y-m-d'),'due_date'=>today()->addDays(14)->format('Y-m-d'),'tax_percent'=>7.5,'discount_kobo'=>0,'items'=>[['description'=>'Installation','quantity'=>1,'unit_price_kobo'=>100000]]]; }