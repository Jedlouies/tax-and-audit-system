<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class VatReportPdfService
{
    protected string $url;
    protected string $key;

    public function __construct()
    {
        $this->url = rtrim(config('services.supabase.url') ?? '', '/');
        $this->key = config('services.supabase.key') ?? '';
    }

    /**
     * Generate VAT Summary Matrix PDF (Branch Columns)
     */
    public function generatePdf(string $userId, int $year = 2026)
    {
        // 1. Fetch Client Details
        $userResp = Http::withoutVerifying()->withHeaders($this->headers())->get("{$this->url}/rest/v1/users", [
            'select' => '*',
            'id'     => 'eq.' . $userId,
        ]);
        $userData = $userResp->successful() ? $userResp->json() : [];
        $user = is_array($userData) && isset($userData[0]) ? $userData[0] : [];

        // 2. Fetch Branches matching the selected client ID
        $branchesResp = Http::withoutVerifying()->withHeaders($this->headers())->get("{$this->url}/rest/v1/branches", [
            'select'  => '*',
            'user_id' => 'eq.' . $userId,
            'order'   => 'id.asc',
        ]);
        $branchesData = $branchesResp->successful() ? $branchesResp->json() : [];
        $branches = is_array($branchesData) ? collect($branchesData) : collect([]);

        // 3. Fetch Sales & Purchases
        $startDate = "{$year}-01-01";
        $endDate   = "{$year}-12-31";

        $salesResp = Http::withoutVerifying()->withHeaders($this->headers())->get("{$this->url}/rest/v1/sales", [
            'select'       => '*',
            'user_id'      => 'eq.' . $userId,
            'invoice_date' => 'gte.' . $startDate,
            'and'          => '(invoice_date.lte.' . $endDate . ')',
        ]);
        $salesData = $salesResp->successful() ? $salesResp->json() : [];
        $sales = is_array($salesData) ? collect($salesData) : collect([]);

        $purchasesResp = Http::withoutVerifying()->withHeaders($this->headers())->get("{$this->url}/rest/v1/purchases", [
            'select'       => '*',
            'user_id'      => 'eq.' . $userId,
            'invoice_date' => 'gte.' . $startDate,
            'and'          => '(invoice_date.lte.' . $endDate . ')',
        ]);
        $purchasesData = $purchasesResp->successful() ? $purchasesResp->json() : [];
        $purchases = is_array($purchasesData) ? collect($purchasesData) : collect([]);

        // 4. Group by Quarters & Months
        $quarters = [
            1 => ['name' => '1stQ', 'months' => [1, 2, 3]],
            2 => ['name' => '2ndQ', 'months' => [4, 5, 6]],
            3 => ['name' => '3rdQ', 'months' => [7, 8, 9]],
            4 => ['name' => '4thQ', 'months' => [10, 11, 12]],
        ];

        $matrix = [];
        $carriedOverInputTax = 6886.96;

        foreach ($quarters as $qNum => $qInfo) {
            $quarterRows = [];

            foreach ($qInfo['months'] as $mNum) {
                $monthDate = Carbon::create($year, $mNum, 1);
                $monthName = $monthDate->format('M');

                // Filter monthly data
                $mSales = $sales->filter(fn($s) => is_array($s) && !empty($s['invoice_date']) && Carbon::parse($s['invoice_date'])->month == $mNum);
                $mPurchases = $purchases->filter(fn($p) => is_array($p) && !empty($p['invoice_date']) && Carbon::parse($p['invoice_date'])->month == $mNum);

                // Compute branch sales
                $branchTotals = [];
                foreach ($branches as $branch) {
                    if (!is_array($branch)) continue;
                    $bId = $branch['id'] ?? null;
                    if (!$bId) continue;

                    $bSales = $mSales->filter(fn($s) => ($s['branch_id'] ?? null) == $bId);
                    $branchTotals[$bId] = $bSales->sum('gross_amount');
                }

                $grossTotal   = $mSales->sum('gross_amount');
                $nonVatExempt = $mSales->filter(fn($s) => ($s['status'] ?? '') === 'exempt')->sum('gross_amount');
                $netOfVat     = $mSales->sum('net_of_vat');
                $outputVat    = $mSales->sum('output_vat');
                $inputVat     = $mPurchases->sum('input_vat');
                $vatPayable   = $outputVat - $inputVat;

                $quarterRows[] = [
                    'month'       => $monthName,
                    'gross_total' => $grossTotal,
                    'branches'    => $branchTotals,
                    'non_vat'     => $nonVatExempt,
                    'net_of_vat'  => $netOfVat,
                    'vat_payable' => $vatPayable,
                ];
            }

            // Quarterly Subtotals
            $qGross = collect($quarterRows)->sum('gross_total');
            $qNonVat = collect($quarterRows)->sum('non_vat');
            $qNetVat = collect($quarterRows)->sum('net_of_vat');
            $qVatPayable = collect($quarterRows)->sum('vat_payable');

            $qBranchTotals = [];
            foreach ($branches as $branch) {
                if (!is_array($branch)) continue;
                $bId = $branch['id'] ?? null;
                if (!$bId) continue;

                $qBranchTotals[$bId] = collect($quarterRows)->sum(fn($r) => $r['branches'][$bId] ?? 0);
            }

            $matrix[] = [
                'quarter'       => $qInfo['name'],
                'rows'          => $quarterRows,
                'q_gross'       => $qGross,
                'q_branches'    => $qBranchTotals,
                'q_non_vat'     => $qNonVat,
                'q_net_vat'     => $qNetVat,
                'q_vat_payable' => $qVatPayable,
            ];
        }

        $data = [
            'user'                => $user,
            'year'                => $year,
            'branches'            => $branches,
            'matrix'              => $matrix,
            'carriedOverInputTax' => $carriedOverInputTax,
        ];

        $pdf = Pdf::loadView('pdf.vat_report', $data)->setPaper('a4', 'landscape');

        return $pdf->stream("VAT_Summary_{$year}.pdf");
    }

    protected function headers(): array
    {
        return [
            'apikey'        => $this->key,
            'Authorization' => 'Bearer ' . $this->key,
        ];
    }
}