<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Http;
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

    /**
     * Generate QAP Report PDF for Standard 4 Quarters (3 months per quarter)
     *
     * @param string $userId      Client / User ID
     * @param int    $quarter     Target Quarter (1, 2, 3, or 4)
     * @param int    $year        Target Year
     * @return \Illuminate\Http\Response
     */
    public function generatePdf(string $userId, int $quarter, int $year)
    {
        // 1. Clamp Quarter strictly between 1 and 4
        $quarter = max(1, min(4, $quarter));

        // 2. Calculate Start and End Dates for 3-Month Quarters
        $startMonth = ($quarter - 1) * 3 + 1;
        $endMonth   = $startMonth + 2;

        $startDate = Carbon::create($year, $startMonth, 1)->startOfMonth()->toDateString();
        $endDate   = Carbon::create($year, $endMonth, 1)->endOfMonth()->toDateString();

        // 3. Fetch Withholding Agent Details
        $userResp = Http::withoutVerifying()->withHeaders($this->headers())->get("{$this->url}/rest/v1/users", [
            'select' => '*',
            'id'     => 'eq.' . $userId,
        ]);
        $user = $userResp->json()[0] ?? [];

        // 4. Fetch Quarter Purchases
        $purchasesResp = Http::withoutVerifying()->withHeaders($this->headers())->get("{$this->url}/rest/v1/purchases", [
            'select'       => '*, entities(*)',
            'user_id'      => 'eq.' . $userId,
            'invoice_date' => 'gte.' . $startDate,
            'and'          => '(invoice_date.lte.' . $endDate . ')',
            'order'        => 'invoice_date.asc',
        ]);

        $purchases = collect($purchasesResp->json() ?? []);

        // 5. Service for Parsing TIN & Name
        $dashboardService = new DashboardService();

        // 6. Group Purchases by Payee TIN & ATC Code
        $grouped = $purchases->groupBy(function ($p) use ($dashboardService) {
            $entity = $p['entities'] ?? $p['entity'] ?? [];
            $rawSupplier = $p['tp_supplier_name'] ?? $p['supplier_name'] ?? $entity['name'] ?? 'UNKNOWN';
            $parsed = $dashboardService->parseTinAndName($rawSupplier, $p['tin'] ?? $entity['tin'] ?? null);
            
            $tinKey = !empty($parsed['tin']) ? $parsed['tin'] : 'NO_TIN';
            $atcKey = $p['atc_code'] ?? $p['atc'] ?? 'WC158';

            return $tinKey . '_' . $atcKey;
        });

        $rows = [];

        // 7. Process Amounts Across the 3 Months of the Quarter
        foreach ($grouped as $items) {
            $first = $items->first();
            $entity = $first['entities'] ?? $first['entity'] ?? [];

            $rawSupplier = $first['tp_supplier_name'] ?? $first['supplier_name'] ?? $entity['name'] ?? 'UNSPECIFIED SUPPLIER';
            $parsed = $dashboardService->parseTinAndName($rawSupplier, $first['tin'] ?? $entity['tin'] ?? null);

            $supplierTin  = !empty($parsed['tin']) ? $parsed['tin'] : '000-000-000-00000';
            $supplierName = strtoupper($parsed['name']);

            $isCorporate = isset($entity['is_corporate']) ? (bool)$entity['is_corporate'] : true;

            // Monthly breakdown across the 3 months
            $m1 = $items->filter(fn($p) => Carbon::parse($p['invoice_date'])->month == $startMonth);
            $m2 = $items->filter(fn($p) => Carbon::parse($p['invoice_date'])->month == ($startMonth + 1));
            $m3 = $items->filter(fn($p) => Carbon::parse($p['invoice_date'])->month == ($startMonth + 2));

            $m1Amt = $m1->sum(fn($p) => $p['taxable_amount'] ?? $p['taxable'] ?? 0);
            $m1Tax = $m1->sum(fn($p) => $p['tax_withheld_2307'] ?? $p['tax_2307'] ?? 0);

            $m2Amt = $m2->sum(fn($p) => $p['taxable_amount'] ?? $p['taxable'] ?? 0);
            $m2Tax = $m2->sum(fn($p) => $p['tax_withheld_2307'] ?? $p['tax_2307'] ?? 0);

            $m3Amt = $m3->sum(fn($p) => $p['taxable_amount'] ?? $p['taxable'] ?? 0);
            $m3Tax = $m3->sum(fn($p) => $p['tax_withheld_2307'] ?? $p['tax_2307'] ?? 0);

            $totQuarterAmt = $m1Amt + $m2Amt + $m3Amt;
            $totQuarterTax = $m1Tax + $m2Tax + $m3Tax;

            // Determine Tax Rate
            $rawRate = $first['tax_rate'] ?? $first['rate'] ?? 0.01;
            $formattedRate = is_numeric($rawRate) ? ($rawRate <= 1 ? ($rawRate * 100) . '%' : $rawRate . '%') : $rawRate;

            $rows[] = [
                'tin'               => $supplierTin,
                'corp_name'         => $isCorporate ? $supplierName : '',
                'ind_name'          => !$isCorporate ? $supplierName : '',
                'atc'               => $first['atc_code'] ?? $first['atc'] ?? 'WC158',
                'tax_rate'          => $formattedRate,
                'm1_amt'            => $m1Amt, 'm1_tax' => $m1Tax,
                'm2_amt'            => $m2Amt, 'm2_tax' => $m2Tax,
                'm3_amt'            => $m3Amt, 'm3_tax' => $m3Tax,
                'quarter_total_amt' => $totQuarterAmt,
                'quarter_total_tax' => $totQuarterTax,
            ];
        }

        // 8. Sort Rows Alphabetically by Payee Name (Corporate or Individual)
        $rows = collect($rows)->sortBy(function ($row) {
            return !empty($row['corp_name']) ? $row['corp_name'] : $row['ind_name'];
        }, SORT_NATURAL | SORT_FLAG_CASE)->values()->toArray();

        $quarterName = strtoupper(Carbon::create($year, $endMonth, 1)->format('F, Y'));

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