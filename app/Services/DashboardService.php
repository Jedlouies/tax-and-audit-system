<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class DashboardService {
    protected string $url;
    protected string $key;

    public function __construct()
    {
        $this->url = config('services.supabase.url');
        $this->key = config('services.supabase.key');
    }

    /**
     * Helper to parse combined "TIN Name" strings if TIN is included in supplier/customer name.
     */
    public function parseTinAndName(?string $rawSupplier, ?string $fallbackTin = null): array
    {
        $rawSupplier = trim($rawSupplier ?? '');

        if (preg_match('/^(\d{3}-\d{3}-\d{3}-\d{3,5})\s+(.*)$/', $rawSupplier, $matches)) {
            return [
                'tin' => $matches[1],
                'name' => trim($matches[2])
            ];
        }

        return [
            'tin' => $fallbackTin ?? '',
            'name' => $rawSupplier
        ];
    }

    public function index(Request $request) {

        $clients = [];
        $sales = [];
        $purchases = [];
        $payroll = [];
        $summary = null;
        $activeClient = null;

        $activeClientId = $request->get('client_id', session('active_client_id'));

        if ($activeClientId) {
            session(['active_client_id' => $activeClientId]);
        } else {
            session()->forget('active_client_id');
        }

        // Default date range: current month
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->toDateString());

        try 
        {    
            // 1. Fetch Clients
            $clientsResponse = Http::withoutVerifying()
            ->withHeaders($this->headers())
            ->get("{$this->url}/rest/v1/users", [
                'select' => '*',
                'role'   => 'eq.client',
            ]);

            if ($clientsResponse->successful()) {
                $clients = $clientsResponse->json();
                $activeClient = collect($clients)->firstWhere('id', (int)$activeClientId) 
                             ?? collect($clients)->firstWhere('id', (string)$activeClientId);
            }
            
            if ($activeClientId) {

                // 2. Fetch Sales
                $salesResponse = Http::withoutVerifying()
                ->withHeaders($this->headers())
                ->get("{$this->url}/rest/v1/sales", [
                    'select'       => '*',
                    'user_id'      => 'eq.' . $activeClientId,
                    'invoice_date' => 'gte.' . $startDate,
                    'and'          => '(invoice_date.lte.' . $endDate . ')',
                ]);
                if ($salesResponse->successful()) {
                    $sales = $salesResponse->json();
                }

                // 3. Fetch Purchases
                $purchasesResponse = Http::withoutVerifying()
                ->withHeaders($this->headers())
                ->get("{$this->url}/rest/v1/purchases", [
                    'select'       => '*',
                    'user_id'      => 'eq.' . $activeClientId,
                    'invoice_date' => 'gte.' . $startDate,
                    'and'          => '(invoice_date.lte.' . $endDate . ')',
                ]);
                if ($purchasesResponse->successful()) {
                    $purchases = $purchasesResponse->json();
                }

                // 4. Fetch Employees
                $employeesResponse = Http::withoutVerifying()
                ->withHeaders($this->headers())
                ->get("{$this->url}/rest/v1/employees", [
                    'select'  => '*',
                    'user_id' => 'eq.' . $activeClientId,
                ]);

                $employeesMap = collect();
                if ($employeesResponse->successful()) {
                    $employeesMap = collect($employeesResponse->json())->keyBy('id');
                }

                // 5. Fetch Payroll Entries
                $employeeIds = $employeesMap->keys()->all();
                if (!empty($employeeIds)) {
                    $payrollResponse = Http::withoutVerifying()
                    ->withHeaders($this->headers())
                    ->get("{$this->url}/rest/v1/payroll_entries", [
                        'select'      => '*',
                        'employee_id' => 'in.(' . implode(',', $employeeIds) . ')',
                        'period_date' => 'gte.' . $startDate,
                        'and'         => '(period_date.lte.' . $endDate . ')',
                    ]);

                    if ($payrollResponse->successful()) {
                        $payroll = collect($payrollResponse->json())->map(function ($p) use ($employeesMap) {
                            $emp = $employeesMap->get($p['employee_id'] ?? null);
                            $p['full_name'] = $emp['full_name'] ?? 'N/A';
                            $p['position']  = $emp['position'] ?? '-';
                            $p['tin']       = $emp['tin'] ?? '-';
                            $p['is_mwe']    = $emp['is_mwe'] ?? false;
                            return $p;
                        })
                        ->sortBy('full_name', SORT_NATURAL | SORT_FLAG_CASE)
                        ->values()
                        ->toArray();
                    }
                }

                // 6. Map Entities and Branches into Sales & Purchases
                $salesEntityIds = collect($sales)->pluck('entity_id')->filter()->toArray();
                $purchasesEntityIds = collect($purchases)->pluck('entity_id')->filter()->toArray();
                $allEntityIds = array_unique(array_merge($salesEntityIds, $purchasesEntityIds));

                $branchIds = collect($sales)->pluck('branch_id')->filter()->unique()->values()->all();

                $entitiesMap = collect();
                if (!empty($allEntityIds)) {
                    $entitiesResp = Http::withoutVerifying()
                    ->withHeaders($this->headers())
                    ->get("{$this->url}/rest/v1/entities", [
                        'select' => '*',
                        'id'     => 'in.(' . implode(',', $allEntityIds) . ')',
                    ]);

                    if ($entitiesResp->successful()) {
                        $entitiesMap = collect($entitiesResp->json() ?? [])->keyBy('id');
                    }
                }

                $branchesMap = collect();
                if (!empty($branchIds)) {
                    $branchesResp = Http::withoutVerifying()
                    ->withHeaders($this->headers())
                    ->get("{$this->url}/rest/v1/branches", [
                        'select' => '*',
                        'id'     => 'in.(' . implode(',', $branchIds) . ')',
                    ]);

                    if ($branchesResp->successful()) {
                        $branchesMap = collect($branchesResp->json() ?? [])->keyBy('id');
                    }
                }

                // Format & Sort Sales Alphabetically
                $sales = collect($sales)->map(function ($s) use ($entitiesMap, $branchesMap) {
                    $entity = $entitiesMap->get($s['entity_id'] ?? null);
                    $branch = $branchesMap->get($s['branch_id'] ?? null);

                    $rawName = $s['supplier_name'] ?? $s['customer_name'] ?? $entity['name'] ?? 'N/A';
                    $parsed = $this->parseTinAndName($rawName, $s['tin'] ?? $entity['tin'] ?? null);

                    $s['parsed_tin'] = $parsed['tin'];
                    $s['parsed_name'] = $parsed['name'];
                    $s['customer_name'] = $parsed['name'];
                    $s['branch'] = $branch['branch_name'] ?? 'MAIN';
                    return $s;
                })
                ->sortBy('parsed_name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->toArray();

                // Format & Sort Purchases Alphabetically
                $purchases = collect($purchases)->map(function ($p) use ($entitiesMap) {
                    $entity = $entitiesMap->get($p['entity_id'] ?? null);
                    $rawName = $p['tp_supplier_name'] ?? $p['supplier_name'] ?? $entity['name'] ?? 'N/A';
                    $parsed = $this->parseTinAndName($rawName, $p['tin'] ?? $entity['tin'] ?? null);

                    $p['parsed_tin'] = $parsed['tin'];
                    $p['parsed_name'] = $parsed['name'];
                    $p['entity_name'] = $parsed['name'];
                    return $p;
                })
                ->sortBy('parsed_name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->toArray();

                // 7. Calculate Financial Summaries
                $salesCollection = collect($sales);
                $purchasesCollection = collect($purchases);

                $outputVat = $salesCollection->sum('output_vat');
                $inputVat = $purchasesCollection->sum('input_vat');
                $vatPayable = $outputVat - $inputVat;

                $summary = [
                    'totalSales'       => $salesCollection->sum('gross_amount'),
                    'totalNet'         => $salesCollection->sum('net_of_vat'),
                    'outputVat'        => $outputVat,

                    'totalPurchases'   => $purchasesCollection->sum('gross_vat') ?: $purchasesCollection->sum('net_amount'),
                    'taxablePurchases' => $purchasesCollection->sum('taxable_amount'),
                    'inputVat'         => $inputVat,
                    'taxWithheld'      => $purchasesCollection->sum('tax_withheld_2307'),
                    'vatPayable'       => $vatPayable,
                ];
            }

        } catch (Exception $e) {
            Log::warning("Failed to Fetch Data: " . $e->getMessage());
        }

        return view('Admin.dashboard', compact(
            'clients', 
            'summary', 
            'activeClientId',
            'activeClient', 
            'startDate', 
            'endDate', 
            'sales',      
            'purchases',
            'payroll'
        ));
    }

    protected function headers(): array
    {
        return [
            'apikey'        => $this->key,
            'Authorization' => 'Bearer ' . $this->key,
        ];
    }
}