<?php

use App\Services\BusinessDocumentService;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed();
    $this->actingAs(demoUser());
});
function staffDocumentBody(string $template = 'offer-letter'): array
{
    return ['template' => $template, 'title' => 'Offer for Example Employee', 'body' => "Example Company & Partners\nPrivate wording <script>alert(1)</script>"];
}
it('provides fourteen templates and a separate encrypted company definition', function () {
    $this->getJson('/api/business-documents/setup')->assertOk()->assertJsonCount(14, 'templates')->assertJsonPath('profile.company_name', demoUser()->business->name);
    $name = demoUser()->business->name;
    $this->putJson('/api/business-documents/profile', ['company_name' => 'Custom Legal Company', 'company_definition' => 'Specialist maintenance', 'values' => 'Care and accuracy'])->assertOk();
    expect(demoUser()->business->fresh()->name)->toBe($name);
    expect(DB::table('business_document_profiles')->value('definition'))->not->toContain('Custom Legal Company');
    $this->getJson('/api/business-documents/setup')->assertJsonPath('profile.values', 'Care and accuracy');
});
it('keeps saved text encrypted and snapshots company definitions', function () {
    $id = $this->postJson('/api/business-documents', staffDocumentBody())->assertCreated()->json('id');
    expect(DB::table('business_documents')->where('id', $id)->value('content'))->not->toContain('Private wording');
    $this->putJson('/api/business-documents/profile', ['company_name' => 'Later name'])->assertOk();
    $this->putJson('/api/business-documents/'.$id, staffDocumentBody() + ['business_id' => demoUser('owner@kemtech.test')->business_id])->assertOk()->assertJsonPath('company.company_name', demoUser()->business->name);
    $this->getJson('/api/business-documents')->assertJsonCount(1, 'data')->assertJsonMissingPath('data.0.content');
    $this->putJson('/api/business-documents/'.$id, staffDocumentBody('nda'))->assertUnprocessable();
    $this->deleteJson('/api/business-documents/'.$id)->assertNoContent();
    $this->getJson('/api/business-documents/'.$id)->assertNotFound();
});
it('isolates documents profiles and downloads between businesses and excludes staff', function () {
    $id = $this->postJson('/api/business-documents', staffDocumentBody())->json('id');
    $this->putJson('/api/business-documents/profile', ['company_name' => 'Private tenant'])->assertOk();
    $this->actingAs(demoUser('owner@kemtech.test'));
    $this->getJson('/api/business-documents')->assertJsonCount(0, 'data');
    foreach (['', '/download/pdf', '/download/docx'] as $suffix) {
        $this->getJson('/api/business-documents/'.$id.$suffix)->assertNotFound();
    }
    $this->putJson('/api/business-documents/'.$id, staffDocumentBody())->assertNotFound();
    $this->deleteJson('/api/business-documents/'.$id)->assertNotFound();
    $this->getJson('/api/business-documents/setup')->assertJsonPath('profile.company_name', demoUser('owner@kemtech.test')->business->name);
    $this->actingAs(demoUser('staff@brightspark.test'));
    foreach (['/setup', '', '/'.$id, '/'.$id.'/download/pdf'] as $suffix) {
        $this->getJson('/api/business-documents'.$suffix)->assertForbidden();
    }
    $this->postJson('/api/business-documents', staffDocumentBody())->assertForbidden();
    $this->putJson('/api/business-documents/profile', ['company_name' => 'No'])->assertForbidden();
    $this->putJson('/api/workspace/preferences', ['mode' => 'custom', 'enabled_modules' => ['business_documents']])->assertUnprocessable();
});
it('exports each text template as valid Word and PDF without executing markup', function () {
    $this->withoutMiddleware(ThrottleRequests::class);
    foreach (app(BusinessDocumentService::class)->catalog() as $t) {
        if ($t['id'] === 'salary-structure') {
            continue;
        }
        $id = $this->postJson('/api/business-documents', staffDocumentBody($t['id']))->assertCreated()->json('id');
        $res = $this->get('/api/business-documents/'.$id.'/download/docx')->assertOk();
        $file = tempnam(sys_get_temp_dir(), 'test-doc-');
        file_put_contents($file, $res->getContent());
        $zip = new ZipArchive;
        expect($zip->open($file))->toBeTrue();
        $xml = $zip->getFromName('word/document.xml');
        expect($xml)->toContain('&lt;script&gt;')->not->toContain('<script>');
        $doc = new DOMDocument;
        expect($doc->loadXML($xml))->toBeTrue();
        $zip->close();
        unlink($file);
        $pdf = $this->get('/api/business-documents/'.$id.'/download/pdf')->assertOk();
        expect($pdf->getContent())->toStartWith('%PDF');
        expect($pdf->headers->get('Cache-Control'))->toContain('no-store');
    }
});
it('validates salary precision and exports rounded forecasts formulas and totals', function () {
    $body = staffDocumentBody('salary-structure') + ['salary' => ['currency' => 'NGN', 'increase' => '20', 'years' => 3, 'rows' => [['role' => 'Engineer', 'amount' => '100.05'], ['role' => '=HYPERLINK("bad")', 'amount' => '50']]]];
    $id = $this->postJson('/api/business-documents', $body)->assertCreated()->json('id');
    $f = app(BusinessDocumentService::class)->forecast($body['salary']);
    expect($f['rows'][0]['values'])->toBe([10005, 12006, 14407])->and($f['totals'])->toBe([15005, 18006, 21607]);
    $res = $this->get('/api/business-documents/'.$id.'/download/xlsx')->assertOk();
    $file = tempnam(sys_get_temp_dir(), 'test-xlsx-');
    file_put_contents($file, $res->getContent());
    $z = new ZipArchive;
    $z->open($file);
    $xml = $z->getFromName('xl/worksheets/sheet1.xml');
    expect($xml)->toContain('ROUND(B6*(1+$B$3),2)', 'SUM(B6:B7)', 't="inlineStr"')->not->toContain('<f>HYPERLINK');
    $z->close();
    unlink($file);
    $body['salary']['currency'] = 'JPY';
    $this->postJson('/api/business-documents', $body)->assertUnprocessable();
    $body['salary']['currency'] = 'USD';
    $body['salary']['increase'] = '1e-8';
    $this->postJson('/api/business-documents', $body)->assertUnprocessable();
    $this->get('/api/business-documents/'.$id.'/download/pdf')->assertOk();
});
it('rejects unsupported templates formats control characters and oversize inputs', function () {
    $this->postJson('/api/business-documents', staffDocumentBody('unknown'))->assertUnprocessable();
    $b = staffDocumentBody();
    $b['body'] = "bad\x01text";
    $this->postJson('/api/business-documents', $b)->assertUnprocessable();
    $b['body'] = str_repeat('a', 30001);
    $this->postJson('/api/business-documents', $b)->assertUnprocessable();
    $id = $this->postJson('/api/business-documents', staffDocumentBody())->json('id');
    $this->getJson('/api/business-documents/'.$id.'/download/xlsx')->assertUnprocessable();
    $this->getJson('/api/business-documents/'.$id.'/download/html')->assertNotFound();
});
