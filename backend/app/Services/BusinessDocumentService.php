<?php

namespace App\Services;

use App\Models\BusinessDocument;
use App\Models\BusinessDocumentProfile;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BusinessDocumentService
{
    public const PROFILE = ['company_name', 'company_address', 'company_email', 'company_phone', 'registration_number', 'company_definition', 'values', 'policies', 'signatory_name', 'signatory_title'];

    public function catalog(): array
    {
        return json_decode(file_get_contents(resource_path('data/business-documents.json')), true, 512, JSON_THROW_ON_ERROR);
    }

    public function profile(Request $r): array
    {
        $b = $r->user()->business;
        $defaults = array_fill_keys(self::PROFILE, '');

        return array_replace($defaults, ['company_name' => $b->name, 'company_address' => $b->address ?? '', 'company_email' => $b->email ?? '', 'company_phone' => $b->phone ?? ''], BusinessDocumentProfile::first()?->definition ?? []);
    }

    public function validate(Request $r, ?BusinessDocument $document = null): array
    {
        $d = $r->validate(['template' => ['required', Rule::in(array_column($this->catalog(), 'id'))], 'title' => 'required|string|max:160', 'body' => 'required|string|max:30000', 'salary' => 'nullable|array:currency,increase,years,rows', 'salary.currency' => ['required_if:template,salary-structure', Rule::in(app(CurrencyService::class)->codes())], 'salary.increase' => ['required_if:template,salary-structure', 'numeric', 'min:0', 'max:100', 'regex:/^\d{1,3}(?:\.\d{1,2})?$/D'], 'salary.years' => 'required_if:template,salary-structure|integer|min:1|max:10', 'salary.rows' => 'required_if:template,salary-structure|array|min:1|max:50', 'salary.rows.*' => 'array:role,amount', 'salary.rows.*.role' => 'required|string|max:120', 'salary.rows.*.amount' => ['required', 'string', 'regex:/^\d{1,9}(?:\.\d{1,4})?$/D']]);
        if ($document && $document->template !== $d['template']) {
            throw ValidationException::withMessages(['template' => 'A saved document cannot change its template. Create another draft instead.']);
        }
        // XML 1.0 cannot encode control characters. Reject rather than silently corrupt exports.
        foreach ([$d['title'], $d['body'], ...array_values($this->profile($r))] as $s) {
            if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', $s)) {
                throw ValidationException::withMessages(['body' => 'Remove unsupported control characters.']);
            }
        }
        $salary = $d['template'] === 'salary-structure' ? $d['salary'] : null;
        if ($salary) {
            foreach ($salary['rows'] as $row) {
                if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', $row['role'])) {
                    throw ValidationException::withMessages(['salary' => 'Remove unsupported control characters from role names.']);
                }
            }
            $this->forecast($salary);
        }

        return ['title' => $d['title'], 'body' => $d['body'], 'company' => $document?->content['company'] ?? $this->profile($r), 'salary' => $salary];
    }

    public function forecast(array $salary): array
    {
        $money = app(CurrencyService::class);
        $digits = $money->precision($salary['currency']);
        $totals = array_fill(0, (int) $salary['years'], 0);
        $rows = [];
        foreach ($salary['rows'] as $row) {
            $minor = $money->minor($row['amount'], $salary['currency']);
            $values = [];
            for ($y = 0; $y < (int) $salary['years']; $y++) {
                if ($y) {
                    $minor = (int) bcadd(bcmul((string) $minor, bcadd('1', bcdiv((string) $salary['increase'], '100', 8), 8), 8), '0.5', 0);
                }
                if ($minor > 1000000000000 || $totals[$y] + $minor > 50000000000000) {
                    throw ValidationException::withMessages(['salary' => 'Salary projection exceeds supported limits.']);
                }
                $values[] = $minor;
                $totals[$y] += $minor;
            }
            $rows[] = ['role' => $row['role'], 'values' => $values];
        }

        return ['currency' => $salary['currency'], 'digits' => $digits, 'rows' => $rows, 'totals' => $totals];
    }
}
