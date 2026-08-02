<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class QapPdfService
{
    protected string $url;
    protected string $key;

    public function __construct()
    {
        $this->url = config('services.supabase.url');
        $this->key = config('services.supabase.key');
    }

    public function generatePdf(string $userId, int $quarter, int $year)
    {
        $startMonth = ($quarter - 1) * 3 + 1;
        $startDate = Carbon::create($year, $startMonth, 1)->startOfMonth()->toDateString();
        $endDate = Carbon::create($year, $startMonth + 2, 1)->endOfMonth()->toDateString();

        // 1. Fetch User Details (Withholding Agent)
        $userResp = Http::withoutVerifying()->withHeaders($this->headers())->get("{$this->url}/rest/v1/users", [
            'select' => '*',
            'id'     => 'eq.' . $userId,
        ]);
        $user = $userResp->json()[0] ?? [];

        // 2. Fetch Purchases for Quarter with Joined Entities
        $purchasesResp = Http::withoutVerifying()->withHeaders($this->headers())->get("{$this->url}/rest/v1/purchases", [
            'select'       => '*, entities(*)',
            'user_id'      => 'eq.' . $userId,
            'invoice_date' => 'gte.' . $startDate,
            'and'          => '(invoice_date.lte.' . $endDate . ')',
            'order'        => 'invoice_date.asc',
        ]);

        $purchases = collect($purchasesResp->json() ?? []);

        // 3. Fetch Entities for TIN Lookup
        $entitiesResp = Http::withoutVerifying()->withHeaders($this->headers())->get("{$this->url}/rest/v1/entities", [
            'select'  => '*',
            'user_id' => 'eq.' . $userId,
        ]);

        $entitiesMap = collect();
        if ($entitiesResp->successful()) {
            $entitiesMap = collect($entitiesResp->json() ?? [])->keyBy(fn($item) => (int)$item['id']);
        }

        // 4. Group Purchases by Entity and ATC Code
        $grouped = $purchases->groupBy(fn($p) => ($p['entity_id'] ?? '0') . '_' . ($p['atc_code'] ?? 'WC158'));
        $rows = [];

        foreach ($grouped as $items) {
            $first = $items->first();
            $entityId = isset($first['entity_id']) ? (int)$first['entity_id'] : null;

            // Extract Entity Object for TIN and Corporate status
            $entity = $first['entities'] 
                   ?? $first['entity'] 
                   ?? ($entityId ? $entitiesMap->get($entityId) : null)
                   ?? [];

            $isCorporate = isset($entity['is_corporate']) ? (bool)$entity['is_corporate'] : true;
            $entityTin   = !empty($entity['tin']) ? $entity['tin'] : '000-000-000-0000';

            // 🌟 Use particular_category instead of entity name
            $categoryName = !empty($first['particular_category']) 
                ? $first['particular_category'] 
                : 'General Expense';

            // Filter amounts by month
            $m1 = $items->filter(fn($p) => Carbon::parse($p['invoice_date'])->month == $startMonth);
            $m2 = $items->filter(fn($p) => Carbon::parse($p['invoice_date'])->month == ($startMonth + 1));
            $m3 = $items->filter(fn($p) => Carbon::parse($p['invoice_date'])->month == ($startMonth + 2));

            $m1Amt = $m1->sum('taxable_amount'); $m1Tax = $m1->sum('tax_withheld_2307');
            $m2Amt = $m2->sum('taxable_amount'); $m2Tax = $m2->sum('tax_withheld_2307');
            $m3Amt = $m3->sum('taxable_amount'); $m3Tax = $m3->sum('tax_withheld_2307');

            $totQuarterAmt = $m1Amt + $m2Amt + $m3Amt;
            $totQuarterTax = $m1Tax + $m2Tax + $m3Tax;

            $rawRate = $first['tax_rate'] ?? 0.0100;
            $formattedRate = ($rawRate * 100) . '%';

            $rows[] = [
                'tin'               => $entityTin,
                'corp_name'         => $isCorporate ? $categoryName : '',
                'ind_name'          => !$isCorporate ? $categoryName : '',
                'atc'               => $first['atc_code'] ?? 'WC158',
                'tax_rate'          => $formattedRate,
                'm1_amt'            => $m1Amt, 'm1_tax' => $m1Tax,
                'm2_amt'            => $m2Amt, 'm2_tax' => $m2Tax,
                'm3_amt'            => $m3Amt, 'm3_tax' => $m3Tax,
                'quarter_total_amt' => $totQuarterAmt,
                'quarter_total_tax' => $totQuarterTax,
            ];
        }

        $quarterName = strtoupper(Carbon::create($year, $startMonth + 2, 1)->format('F, Y'));

        $data = [
            'user'          => $user,
            'rows'          => $rows,
            'quarter'       => $quarter,
            'quarterName'   => $quarterName,
            'grandTotalAmt' => collect($rows)->sum('quarter_total_amt'),
            'grandTotalTax' => collect($rows)->sum('quarter_total_tax'),
        ];

        $pdf = Pdf::loadView('pdf.qap_report', $data)->setPaper('a4', 'landscape');

        return $pdf->stream("BIR_QAP_Q{$quarter}_{$year}.pdf");
    }

    protected function headers(): array
    {
        return [
            'apikey'        => $this->key,
            'Authorization' => 'Bearer ' . $this->key,
        ];
    }
}