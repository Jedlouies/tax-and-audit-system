<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class WTaxExpandedPdfService
{
    protected string $url;
    protected string $key;

    public function __construct()
    {
        $this->url = rtrim(config('services.supabase.url') ?? '', '/');
        $this->key = config('services.supabase.key') ?? '';
    }

    /**
     * Generate Expanded Withholding Tax Summary Report PDF
     */
    public function generatePdf(string $userId, int $year = 2026)
    {
        // 1. Fetch Client/User Details
        $userResp = Http::withoutVerifying()->withHeaders($this->headers())->get("{$this->url}/rest/v1/users", [
            'select' => '*',
            'id'     => 'eq.' . $userId,
        ]);
        $userData = $userResp->successful() ? $userResp->json() : [];
        $user = is_array($userData) && isset($userData[0]) ? $userData[0] : [];

        // 2. Fetch Full Year Purchases/Expenses
        $startDate = "{$year}-01-01";
        $endDate   = "{$year}-12-31";

        $purchasesResp = Http::withoutVerifying()->withHeaders($this->headers())->get("{$this->url}/rest/v1/purchases", [
            'select'       => '*',
            'user_id'      => 'eq.' . $userId,
            'invoice_date' => 'gte.' . $startDate,
            'and'          => '(invoice_date.lte.' . $endDate . ')',
        ]);
        $purchasesData = $purchasesResp->successful() ? $purchasesResp->json() : [];
        $purchases = is_array($purchasesData) ? collect($purchasesData) : collect([]);

        // 3. Define Standard Target ATC Columns
        $atcColumns = ['WC120', 'WC158', 'WI100', 'WI120', 'WI158'];

        // 4. Build Monthly & Quarterly Breakdown Matrix
        $quarters = [
            1 => ['name' => '1stQ', 'months' => [1, 2, 3]],
            2 => ['name' => '2ndQ', 'months' => [4, 5, 6]],
            3 => ['name' => '3rdQ', 'months' => [7, 8, 9]],
            4 => ['name' => '4thQ', 'months' => [10, 11, 12]],
        ];

        $matrix = [];
        $carriedOverPayment = 7595.53; // Carried over payment balance

        foreach ($quarters as $qNum => $qInfo) {
            $quarterRows = [];

            foreach ($qInfo['months'] as $mNum) {
                $monthDate = Carbon::create($year, $mNum, 1);
                $monthName = $monthDate->format('M');

                // Filter purchases for current month
                $mPurchases = $purchases->filter(fn($p) => is_array($p) && !empty($p['invoice_date']) && Carbon::parse($p['invoice_date'])->month == $mNum);

                // Compute income payment amounts per ATC Column
                $atcTotals = [];
                foreach ($atcColumns as $atc) {
                    $atcSales = $mPurchases->filter(fn($p) => strtoupper($p['atc_code'] ?? $p['atc'] ?? '') === $atc);
                    $atcTotals[$atc] = $atcSales->sum(fn($p) => $p['taxable_amount'] ?? $p['taxable'] ?? 0);
                }

                // Calculate Withholding Tax Payable (Sum of 2307 Withheld Tax)
                $wtaxPayable = $mPurchases->sum(fn($p) => $p['tax_withheld_2307'] ?? $p['tax_2307'] ?? 0);

                $quarterRows[] = [
                    'month'        => $monthName,
                    'atc_totals'   => $atcTotals,
                    'wtax_payable' => $wtaxPayable,
                ];
            }

            // Calculate Quarterly Subtotals
            $qAtcTotals = [];
            foreach ($atcColumns as $atc) {
                $qAtcTotals[$atc] = collect($quarterRows)->sum(fn($r) => $r['atc_totals'][$atc] ?? 0);
            }

            $qWtaxPayable = collect($quarterRows)->sum('wtax_payable');

            $matrix[] = [
                'quarter'        => $qInfo['name'],
                'rows'           => $quarterRows,
                'q_atc_totals'   => $qAtcTotals,
                'q_wtax_payable' => $qWtaxPayable,
            ];
        }

        $data = [
            'user'               => $user,
            'year'               => $year,
            'atcColumns'         => $atcColumns,
            'matrix'             => $matrix,
            'carriedOverPayment' => $carriedOverPayment,
        ];

        $pdf = Pdf::loadView('pdf.wtax_expanded_report', $data)->setPaper('a4', 'landscape');

        return $pdf->stream("Withholding_Tax_Expanded_Summary_{$year}.pdf");
    }

    protected function headers(): array
    {
        return [
            'apikey'        => $this->key,
            'Authorization' => 'Bearer ' . $this->key,
        ];
    }
}