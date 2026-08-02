<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TAS | Admin Dashboard</title>
    <!-- Tailwind CSS CDN for quick clean styling -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased">

    <!-- Top Navigation / Header -->
    <header class="bg-white shadow border-b border-gray-200 px-6 py-4 flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Admin Portal</h1>
            <p class="text-sm text-gray-500">Tax Accounting System Dashboard</p>
        </div>

        <!-- Filter Form -->
        <form action="{{ route('dashboard') }}" method="GET" id="clientContextForm" class="flex flex-wrap items-center gap-3">
            <!-- Client Selector -->
            <div>
                <label for="client_select" class="block text-xs font-semibold text-gray-600 uppercase">Client</label>
                <select name="client_id" id="client_select" onchange="this.form.submit()" class="mt-1 border border-gray-300 rounded-md px-3 py-1.5 text-sm bg-white focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Select Client --</option>
                    @foreach($clients ?? [] as $client)
                        @php
                            $clientId = $client['id'] ?? null;
                            $clientName = $client['name'] ?? 'Unknown Client';
                        @endphp
                        <option value="{{ $clientId }}" {{ ((string) request('client_id') === (string) $clientId || (string) ($activeClientId ?? '') === (string) $clientId) ? 'selected' : '' }}>
                            {{ $clientName }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- BIR Period Preset Selector -->
            <div>
                <label for="period_preset" class="block text-xs font-semibold text-gray-600 uppercase">BIR Period</label>
                <select name="period_preset" id="period_preset" onchange="handlePeriodChange(this.value)" class="mt-1 border border-gray-300 rounded-md px-3 py-1.5 text-sm bg-white focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Custom / Select Period --</option>
                    <optgroup label="Quarters (VAT Form 2550Q / EWT)">
                        <option value="q1_{{ date('Y') }}" {{ request('period_preset') == 'q1_'.date('Y') ? 'selected' : '' }}>Q1 (Jan - Mar)</option>
                        <option value="q2_{{ date('Y') }}" {{ request('period_preset') == 'q2_'.date('Y') ? 'selected' : '' }}>Q2 (Apr - Jun)</option>
                        <option value="q3_{{ date('Y') }}" {{ request('period_preset') == 'q3_'.date('Y') ? 'selected' : '' }}>Q3 (Jul - Sep)</option>
                        <option value="q4_{{ date('Y') }}" {{ request('period_preset') == 'q4_'.date('Y') ? 'selected' : '' }}>Q4 (Oct - Dec)</option>
                    </optgroup>
                    <optgroup label="Monthly (Withholding Tax 1601-C)">
                        <option value="m1_{{ date('Y') }}" {{ request('period_preset') == 'm1_'.date('Y') ? 'selected' : '' }}>January</option>
                        <option value="m2_{{ date('Y') }}" {{ request('period_preset') == 'm2_'.date('Y') ? 'selected' : '' }}>February</option>
                        <option value="m3_{{ date('Y') }}" {{ request('period_preset') == 'm3_'.date('Y') ? 'selected' : '' }}>March</option>
                        <option value="m4_{{ date('Y') }}" {{ request('period_preset') == 'm4_'.date('Y') ? 'selected' : '' }}>April</option>
                        <option value="m5_{{ date('Y') }}" {{ request('period_preset') == 'm5_'.date('Y') ? 'selected' : '' }}>May</option>
                        <option value="m6_{{ date('Y') }}" {{ request('period_preset') == 'm6_'.date('Y') ? 'selected' : '' }}>June</option>
                        <option value="m7_{{ date('Y') }}" {{ request('period_preset') == 'm7_'.date('Y') ? 'selected' : '' }}>July</option>
                        <option value="m8_{{ date('Y') }}" {{ request('period_preset') == 'm8_'.date('Y') ? 'selected' : '' }}>August</option>
                        <option value="m9_{{ date('Y') }}" {{ request('period_preset') == 'm9_'.date('Y') ? 'selected' : '' }}>September</option>
                        <option value="m10_{{ date('Y') }}" {{ request('period_preset') == 'm10_'.date('Y') ? 'selected' : '' }}>October</option>
                        <option value="m11_{{ date('Y') }}" {{ request('period_preset') == 'm11_'.date('Y') ? 'selected' : '' }}>November</option>
                        <option value="m12_{{ date('Y') }}" {{ request('period_preset') == 'm12_'.date('Y') ? 'selected' : '' }}>December</option>
                    </optgroup>
                </select>
            </div>

            <!-- Manual Date Pickers -->
            <div>
                <label for="start_date" class="block text-xs font-semibold text-gray-600 uppercase">From</label>
                <input type="date" name="start_date" id="start_date" value="{{ $startDate ?? '' }}" onchange="this.form.submit()" class="mt-1 border border-gray-300 rounded-md px-2 py-1 text-sm bg-white">
            </div>

            <div>
                <label for="end_date" class="block text-xs font-semibold text-gray-600 uppercase">To</label>
                <input type="date" name="end_date" id="end_date" value="{{ $endDate ?? '' }}" onchange="this.form.submit()" class="mt-1 border border-gray-300 rounded-md px-2 py-1 text-sm bg-white">
            </div>
        </form>
    </header>

    <main class="max-w-7xl mx-auto p-6">
        @if(!empty($activeClientId) && isset($summary))
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                
                <div class="bg-white p-5 rounded-lg shadow border-t-4 border-blue-500">
                    <h2 class="text-lg font-bold text-gray-800 mb-3">Sales Summary</h2>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-gray-500">Gross Sales:</span> <span class="font-semibold">₱{{ number_format($summary['totalSales'], 2) }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Net of VAT:</span> <span class="font-semibold">₱{{ number_format($summary['totalNet'], 2) }}</span></div>
                        <div class="flex justify-between border-t pt-2"><span class="font-medium text-blue-600">Output VAT:</span> <span class="font-bold text-blue-600">₱{{ number_format($summary['outputVat'], 2) }}</span></div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-lg shadow border-t-4 border-orange-500">
                    <h2 class="text-lg font-bold text-gray-800 mb-3">Purchases Summary</h2>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-gray-500">Total Purchases:</span> <span class="font-semibold">₱{{ number_format($summary['totalPurchases'], 2) }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Taxable Amount:</span> <span class="font-semibold">₱{{ number_format($summary['taxablePurchases'], 2) }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">EWT Withheld (2307):</span> <span class="font-semibold">₱{{ number_format($summary['taxWithheld'], 2) }}</span></div>
                        <div class="flex justify-between border-t pt-2"><span class="font-medium text-orange-600">Input VAT:</span> <span class="font-bold text-orange-600">₱{{ number_format($summary['inputVat'], 2) }}</span></div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-lg shadow border-t-4 border-green-500 md:col-span-2 lg:col-span-1">
                    <h2 class="text-lg font-bold text-gray-800 mb-3">BIR Tax Position</h2>
                    <div class="bg-green-50 p-4 rounded-lg border border-green-200 text-center">
                        <p class="text-xs uppercase font-semibold text-green-700">Net VAT Payable / (Creditable)</p>
                        <p class="text-3xl font-extrabold text-green-900 mt-1">₱{{ number_format($summary['vatPayable'] ?? 0, 2) }}</p>
                    </div>
                </div>

            </div>

            <div class="space-y-8">
                
                <!-- Sales Transactions Table -->
                <div class="bg-white shadow rounded-lg p-6 border border-gray-200">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold text-gray-800">Sales Transactions</h2>
                        <span class="text-xs bg-blue-100 text-blue-800 px-2.5 py-1 rounded-full font-semibold">
                            Total: {{ count($sales ?? []) }} records
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-600 border-collapse">
                            <thead class="text-xs uppercase bg-gray-100 text-gray-700">
                                <tr>
                                    <th class="px-4 py-3">Invoice Date</th>
                                    <th class="px-4 py-3">Invoice No.</th>
                                    <th class="px-4 py-3 text-right">Gross Amount</th>
                                    <th class="px-4 py-3 text-right">Net of VAT</th>
                                    <th class="px-4 py-3 text-right">Output VAT</th>
                                    <th class="px-4 py-3 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse($sales ?? [] as $sale)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 whitespace-nowrap">{{ $sale['invoice_date'] ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 font-mono text-gray-900 font-medium">{{ $sale['invoice_no'] ?? '-' }}</td>
                                        <td class="px-4 py-3 text-right font-medium">₱{{ number_format($sale['gross_amount'] ?? 0, 2) }}</td>
                                        <td class="px-4 py-3 text-right">₱{{ number_format($sale['net_of_vat'] ?? 0, 2) }}</td>
                                        <td class="px-4 py-3 text-right text-blue-600 font-medium">₱{{ number_format($sale['output_vat'] ?? 0, 2) }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="text-xs px-2 py-0.5 rounded {{ ($sale['status'] ?? '') === 'approved' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                {{ ucfirst($sale['status'] ?? 'submitted') }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-6 text-center text-gray-400">
                                            No sales transactions recorded for this period.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Purchases Transactions Table -->
                <div class="bg-white shadow rounded-lg p-6 border border-gray-200">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold text-gray-800">Purchases & Expenses</h2>
                        <span class="text-xs bg-orange-100 text-orange-800 px-2.5 py-1 rounded-full font-semibold">
                            Total: {{ count($purchases ?? []) }} records
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-600 border-collapse">
                            <thead class="text-xs uppercase bg-gray-100 text-gray-700">
                                <tr>
                                    <th class="px-4 py-3">Invoice Date</th>
                                    <th class="px-4 py-3">Invoice No.</th>
                                    <th class="px-4 py-3">Category</th>
                                    <th class="px-4 py-3 text-right">Taxable Amount</th>
                                    <th class="px-4 py-3 text-right">Input VAT</th>
                                    <th class="px-4 py-3 text-right">EWT (2307)</th>
                                    <th class="px-4 py-3 text-right">Net Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse($purchases ?? [] as $purchase)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 whitespace-nowrap">{{ $purchase['invoice_date'] ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 font-mono text-gray-900 font-medium">{{ $purchase['invoice_no'] ?? '-' }}</td>
                                        <td class="px-4 py-3 text-gray-500">{{ $purchase['particular_category'] ?? 'General' }}</td>
                                        <td class="px-4 py-3 text-right">₱{{ number_format($purchase['taxable_amount'] ?? 0, 2) }}</td>
                                        <td class="px-4 py-3 text-right text-orange-600 font-medium">₱{{ number_format($purchase['input_vat'] ?? 0, 2) }}</td>
                                        <td class="px-4 py-3 text-right text-gray-500">₱{{ number_format($purchase['tax_withheld_2307'] ?? 0, 2) }}</td>
                                        <td class="px-4 py-3 text-right font-medium text-gray-900">₱{{ number_format($purchase['net_amount'] ?? 0, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-6 text-center text-gray-400">
                                            No purchase records found for this period.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>


                <div class="bg-white p-4 rounded-lg shadow border border-gray-200 mb-6 flex flex-wrap justify-between items-center gap-4">
                    <div>
                        <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wide">Report Generation</h3>
                        <p class="text-xs text-gray-500">Export official BIR compliance reports and summary schedules for {{ $activeClient['name'] ?? 'Selected Client' }}</p>
                    </div>

                    @php
                        // Calculate the target quarter and year based on the selected date filter
                        $selectedMonth = !empty($startDate) ? \Carbon\Carbon::parse($startDate)->month : now()->month;
                        $currentQuarter = ceil($selectedMonth / 3);
                        $currentYear = !empty($startDate) ? \Carbon\Carbon::parse($startDate)->year : now()->year;
                    @endphp

                    <div class="flex flex-wrap items-center gap-2">
                        <!-- VAT Summary (PDF) -->
                        <a href="{{ route('reports.vat_pdf', ['client_id' => $activeClientId, 'start_date' => $startDate, 'end_date' => $endDate]) }}" 
                        target="_blank" 
                        class="inline-flex items-center bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-3 py-2 rounded shadow transition">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 01.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            Export VAT Report (PDF)
                        </a>

                        <!-- SLSP (Excel) - You can keep this or remove if you want EVERYTHING as PDF -->
                        <a href="{{ route('reports.slsp_excel', ['client_id' => $activeClientId, 'start_date' => $startDate, 'end_date' => $endDate]) }}" 
                        class="inline-flex items-center bg-green-600 hover:bg-green-700 text-white text-xs font-semibold px-3 py-2 rounded shadow transition">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Export SLSP (Excel)
                        </a>

                        <!-- NEW: BIR Form 1601-EQ / QAP (PDF) -->
                        <a href="{{ route('reports.qap_pdf', ['client_id' => $activeClientId, 'quarter' => $currentQuarter, 'year' => $currentYear]) }}" 
                        target="_blank"
                        class="inline-flex items-center bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold px-3 py-2 rounded shadow transition">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Export QAP (PDF)
                        </a>
                    </div>
                </div>
                </div>
            </div>

        @else
            <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-6 rounded-lg text-center">
                <p class="font-semibold text-lg">No Client Selected</p>
                <p class="text-sm mt-1">Please choose a managed client from the top dropdown to view their financial and tax summaries.</p>
            </div>
        @endif
    </main>

    <script>
        function handlePeriodChange(val) {
            if (!val) return;
            const year = new Date().getFullYear();
            let start = '', end = '';

            if (val.startsWith('q')) {
                const quarter = val.charAt(1);
                if (quarter === '1') { start = `${year}-01-01`; end = `${year}-03-31`; }
                else if (quarter === '2') { start = `${year}-04-01`; end = `${year}-06-30`; }
                else if (quarter === '3') { start = `${year}-07-01`; end = `${year}-09-30`; }
                else if (quarter === '4') { start = `${year}-10-01`; end = `${year}-12-31`; }
            } else if (val.startsWith('m')) {
                const month = val.split('_')[0].replace('m', '');
                const lastDay = new Date(year, month, 0).getDate();
                const formattedMonth = month.padStart(2, '0');
                start = `${year}-${formattedMonth}-01`;
                end = `${year}-${formattedMonth}-${lastDay}`;
            }

            if (start && end) {
                document.getElementById('start_date').value = start;
                document.getElementById('end_date').value = end;
                document.getElementById('clientContextForm').submit();
            }
        }
    </script>
</body>
</html>