<?php

namespace App\Http\Controllers;

use App\Models\BusinessDocument;
use App\Models\BusinessDocumentProfile;
use App\Services\BusinessDocumentExport;
use App\Services\BusinessDocumentService;
use Illuminate\Http\Request;

class BusinessDocumentController extends Controller
{
    public function setup(Request $r, BusinessDocumentService $s)
    {
        return ['templates' => $s->catalog(), 'profile' => $s->profile($r)];
    }

    public function profile(Request $r, BusinessDocumentService $s)
    {
        $rules = [];
        foreach ($s::PROFILE as $key) {
            $rules[$key] = ($key === 'company_name' ? 'required' : 'nullable').'|string|max:3000';
        }
        $d = $r->validate($rules);
        foreach ($d as $value) {
            if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', $value ?? '')) {
                abort(422, 'Remove unsupported control characters.');
            }
        }
        $p = BusinessDocumentProfile::first() ?? new BusinessDocumentProfile;
        $p->definition = array_replace(array_fill_keys($s::PROFILE, ''), array_map(fn ($v) => $v ?? '', $d));
        $p->save();

        return $s->profile($r);
    }

    public function index()
    {
        return BusinessDocument::orderByDesc('updated_at')->paginate(20)->through(fn ($d) => ['id' => $d->id, 'template' => $d->template, 'title' => $d->content['title'], 'updated_at' => $d->updated_at]);
    }

    public function show(BusinessDocument $businessDocument)
    {
        return ['id' => $businessDocument->id, 'template' => $businessDocument->template, ...$businessDocument->content];
    }

    public function store(Request $r, BusinessDocumentService $s)
    {
        $content = $s->validate($r);
        $d = BusinessDocument::create(['template' => $r->input('template'), 'content' => $content]);

        return response()->json(['id' => $d->id, 'template' => $d->template, ...$d->content], 201);
    }

    public function update(Request $r, BusinessDocument $businessDocument, BusinessDocumentService $s)
    {
        $businessDocument->update(['content' => $s->validate($r, $businessDocument)]);

        return ['id' => $businessDocument->id, 'template' => $businessDocument->template, ...$businessDocument->content];
    }

    public function destroy(BusinessDocument $businessDocument)
    {
        $businessDocument->delete();

        return response()->noContent();
    }

    public function download(BusinessDocument $businessDocument, string $format, BusinessDocumentExport $export)
    {
        abort_unless($format !== 'xlsx' || $businessDocument->template === 'salary-structure', 422, 'Excel is available for salary structures.');
        [$bytes,$mime] = $export->download($businessDocument, $format);

        return response($bytes, 200, ['Content-Type' => $mime, 'Content-Disposition' => 'attachment; filename="business-document-'.$businessDocument->id.'.'.$format.'"', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
