<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TAS | Admin Dashboard</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
        }
    </script>

    <!-- Theme Initialization Script -->
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <!-- Scrollbar Hiding Style Utility -->
    <style>
        /* Hide scrollbars for Chrome, Safari, Edge, and Firefox while retaining functional scroll */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-100 font-sans antialiased transition-colors duration-200">

    <!-- FULLSCREEN LOADING OVERLAY -->
    <div id="loadingOverlay" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 hidden flex-col items-center justify-center text-white transition-opacity duration-200">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-xl flex flex-col items-center gap-3 border border-gray-200 dark:border-gray-700">
            <svg class="animate-spin h-10 w-10 text-blue-600 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Fetching Records...</p>
        </div>
    </div>

    <!-- LOGOUT CONFIRMATION MODAL -->
    <div id="logoutModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-sm w-full p-6 border border-gray-200 dark:border-gray-700 transform transition-all">
            <div class="flex items-center gap-3 text-red-600 dark:text-red-400 mb-4">
                <div class="p-2 bg-red-100 dark:bg-red-900/40 rounded-full">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Confirm Logout</h3>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-6">Are you sure you want to end your session?</p>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeLogoutModal()" class="px-4 py-2 text-xs font-semibold rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    Cancel
                </button>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-lg bg-red-600 hover:bg-red-700 text-white shadow transition">
                        Yes, Logout
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Top Navigation / Header -->
    <header class="bg-white dark:bg-gray-800 shadow border-b border-gray-200 dark:border-gray-700 px-4 sm:px-6 py-4 flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="flex items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Admin Portal</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Tax Accounting System Dashboard</p>
            </div>
        </div>

        <!-- Filter Form & Actions -->
        <div class="flex flex-wrap items-center gap-4">
            <form action="{{ route('dashboard') }}" method="GET" id="clientContextForm" onsubmit="showLoading()" class="flex flex-wrap items-center gap-3">
                <!-- Client Selector -->
                <div>
                    <label for="client_select" class="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Client</label>
                    <select name="client_id" id="client_select" onchange="submitFilterForm()" class="mt-1 border border-gray-300 dark:border-gray-600 rounded-md px-3 py-1.5 text-sm bg-white dark:bg-gray-700 text-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
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
                    <label for="period_preset" class="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">BIR Period</label>
                    <select name="period_preset" id="period_preset" onchange="handlePeriodChange(this.value)" class="mt-1 border border-gray-300 dark:border-gray-600 rounded-md px-3 py-1.5 text-sm bg-white dark:bg-gray-700 text-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Custom / Select Period --</option>
                        <optgroup label="Quarters (VAT Form 2550Q / EWT)" class="bg-white dark:bg-gray-800">
                            <option value="q1_{{ date('Y') }}" {{ request('period_preset') == 'q1_'.date('Y') ? 'selected' : '' }}>Q1 (Jan - Mar)</option>
                            <option value="q2_{{ date('Y') }}" {{ request('period_preset') == 'q2_'.date('Y') ? 'selected' : '' }}>Q2 (Apr - Jun)</option>
                            <option value="q3_{{ date('Y') }}" {{ request('period_preset') == 'q3_'.date('Y') ? 'selected' : '' }}>Q3 (Jul - Sep)</option>
                            <option value="q4_{{ date('Y') }}" {{ request('period_preset') == 'q4_'.date('Y') ? 'selected' : '' }}>Q4 (Oct - Dec)</option>
                        </optgroup>
                        <optgroup label="Monthly (Withholding Tax 1601-C)" class="bg-white dark:bg-gray-800">
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
                    <label for="start_date" class="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">From</label>
                    <input type="date" name="start_date" id="start_date" value="{{ $startDate ?? '' }}" onchange="submitFilterForm()" class="mt-1 border border-gray-300 dark:border-gray-600 rounded-md px-2 py-1 text-sm bg-white dark:bg-gray-700 text-gray-800 dark:text-white">
                </div>

                <div>
                    <label for="end_date" class="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">To</label>
                    <input type="date" name="end_date" id="end_date" value="{{ $endDate ?? '' }}" onchange="submitFilterForm()" class="mt-1 border border-gray-300 dark:border-gray-600 rounded-md px-2 py-1 text-sm bg-white dark:bg-gray-700 text-gray-800 dark:text-white">
                </div>
            </form>

            <div class="flex items-center gap-2 border-l border-gray-200 dark:border-gray-700 pl-4 mt-3 md:mt-0">
                <button onclick="toggleTheme()" type="button" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition" title="Toggle Theme">
                    <svg id="sunIcon" class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <svg id="moonIcon" class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                    </svg>
                </button>

                <button onclick="openLogoutModal()" type="button" class="p-2 rounded-lg bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/60 transition flex items-center gap-1.5 text-xs font-semibold" title="Logout Session">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    <span class="hidden sm:inline">Logout</span>
                </button>
            </div>
        </div>
    </header>

    <!-- FULL WIDTH FLUID CONTAINER -->
    <main class="w-full px-4 sm:px-6 py-6">
        @if(!empty($activeClientId) && isset($summary))

            <!-- REPORT GENERATION EXPORT ACTIONS -->
            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow border border-gray-200 dark:border-gray-700 mb-6 flex flex-wrap justify-between items-center gap-4">
                <div>
                    <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wide">Report Generation</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Export official BIR compliance reports and summary schedules for {{ $activeClient['name'] ?? 'Selected Client' }}</p>
                </div>

                @php
                    $selectedMonth = !empty($startDate) ? \Carbon\Carbon::parse($startDate)->month : now()->month;
                    $currentQuarter = (int) ceil($selectedMonth / 3);
                    $currentYear = !empty($startDate) ? \Carbon\Carbon::parse($startDate)->year : now()->year;
                @endphp

                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('reports.vat_pdf', ['client_id' => $activeClientId, 'start_date' => $startDate, 'end_date' => $endDate]) }}" 
                       target="_blank" 
                       class="inline-flex items-center bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 text-xs font-medium px-3 py-2 rounded shadow-sm transition">
                        <svg class="w-4 h-4 mr-1.5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 01.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        VAT Summary (PDF)
                    </a>

                    <a href="{{ route('reports.wtax_expanded_pdf', ['client_id' => $activeClientId, 'start_date' => $startDate]) }}" 
                        target="_blank"
                        class="inline-flex items-center bg-indigo-50 dark:bg-indigo-900/30 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 border border-indigo-300 dark:border-indigo-700 text-xs font-medium px-3 py-2 rounded shadow-sm transition">
                            <svg class="w-4 h-4 mr-1.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            WTax Expanded Summary (PDF)
                    </a>

                    <a href="{{ route('reports.qap_pdf', ['client_id' => $activeClientId, 'quarter' => $currentQuarter, 'year' => $currentYear]) }}" 
                       target="_blank"
                       class="inline-flex items-center bg-slate-800 dark:bg-blue-600 hover:bg-slate-900 dark:hover:bg-blue-500 text-white text-xs font-medium px-3 py-2 rounded shadow-sm transition">
                        <svg class="w-4 h-4 mr-1.5 text-slate-300 dark:text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                         QAP (PDF)
                    </a>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <div class="bg-white dark:bg-gray-800 p-5 rounded-lg shadow border-t-4 border-blue-500 border-x border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-3">Sales Summary</h2>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-gray-500 dark:text-gray-400">Gross Sales:</span> <span class="font-semibold text-gray-800 dark:text-gray-200">₱{{ number_format($summary['totalSales'], 2) }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500 dark:text-gray-400">Net of VAT:</span> <span class="font-semibold text-gray-800 dark:text-gray-200">₱{{ number_format($summary['totalNet'], 2) }}</span></div>
                        <div class="flex justify-between border-t border-gray-200 dark:border-gray-700 pt-2"><span class="font-medium text-blue-600 dark:text-blue-400">Output VAT:</span> <span class="font-bold text-blue-600 dark:text-blue-400">₱{{ number_format($summary['outputVat'], 2) }}</span></div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 p-5 rounded-lg shadow border-t-4 border-orange-500 border-x border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-3">Purchases Summary</h2>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-gray-500 dark:text-gray-400">Total Purchases:</span> <span class="font-semibold text-gray-800 dark:text-gray-200">₱{{ number_format($summary['totalPurchases'], 2) }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500 dark:text-gray-400">Taxable Amount:</span> <span class="font-semibold text-gray-800 dark:text-gray-200">₱{{ number_format($summary['taxablePurchases'], 2) }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500 dark:text-gray-400">EWT Withheld (2307):</span> <span class="font-semibold text-gray-800 dark:text-gray-200">₱{{ number_format($summary['taxWithheld'], 2) }}</span></div>
                        <div class="flex justify-between border-t border-gray-200 dark:border-gray-700 pt-2"><span class="font-medium text-orange-600 dark:text-orange-400">Input VAT:</span> <span class="font-bold text-orange-600 dark:text-orange-400">₱{{ number_format($summary['inputVat'], 2) }}</span></div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 p-5 rounded-lg shadow border-t-4 border-green-500 border-x border-b border-gray-200 dark:border-gray-700 md:col-span-2 lg:col-span-1">
                    <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-3">BIR Tax Position</h2>
                    <div class="bg-green-50 dark:bg-gray-900 p-4 rounded-lg border border-green-200 dark:border-green-800/50 text-center">
                        <p class="text-xs uppercase font-semibold text-green-700 dark:text-green-400">Net VAT Payable / (Creditable)</p>
                        <p class="text-3xl font-extrabold text-green-900 dark:text-green-300 mt-1">₱{{ number_format($summary['vatPayable'] ?? 0, 2) }}</p>
                    </div>
                </div>
            </div>

            <!-- TAB MENU & SEARCH BAR SECTION -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 border border-gray-200 dark:border-gray-700">
                
                <div class="flex flex-col md:flex-row justify-between items-center gap-4 pb-4 border-b border-gray-200 dark:border-gray-700 mb-6">
                    <div class="flex flex-wrap items-center gap-2 bg-gray-100 dark:bg-gray-900 p-1.5 rounded-lg">
                        <button type="button" id="tabBtnSales" onclick="switchTab('sales')" 
                                class="px-4 py-2 text-sm font-bold rounded-md transition-all duration-150 bg-white dark:bg-gray-800 text-blue-600 dark:text-blue-400 shadow">
                            Sales ({{ count($sales ?? []) }})
                        </button>
                        <button type="button" id="tabBtnPurchases" onclick="switchTab('purchases')" 
                                class="px-4 py-2 text-sm font-semibold rounded-md transition-all duration-150 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                            Purchases ({{ count($purchases ?? []) }})
                        </button>
                        <button type="button" id="tabBtnPayroll" onclick="switchTab('payroll')" 
                                class="px-4 py-2 text-sm font-semibold rounded-md transition-all duration-150 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                            Employees & Payroll ({{ count($payroll ?? []) }})
                        </button>
                    </div>

                    <div class="flex items-center gap-3 w-full md:w-auto">
                        <div class="relative w-full md:w-80">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <input type="text" id="tableSearchInput" onkeyup="filterTable()" 
                                   placeholder="Search invoice, name, TIN, position..." 
                                   class="w-full pl-9 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>

                @php
                    // Sort sales and purchases collections newest to oldest
                    $sortedSales = collect($sales ?? [])->sortByDesc(fn($s) => $s['invoice_date'] ?? $s['date'] ?? '')->values();
                    $sortedPurchases = collect($purchases ?? [])->sortByDesc(fn($p) => $p['invoice_date'] ?? $p['date'] ?? '')->values();
                @endphp

                <!-- TAB 1: SALES TRANSACTIONS TABLE -->
                <div id="tabContentSales" class="tab-content">
                    <div class="overflow-auto no-scrollbar border border-gray-200 dark:border-gray-700 rounded-lg max-h-[calc(100vh-220px)] relative">
                        <table id="salesTable" class="w-full text-sm text-left text-gray-600 dark:text-gray-300 border-collapse">
                            <thead>
                                <tr class="text-xs uppercase bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                                    <th class="sticky top-0 z-20 px-4 py-3 bg-gray-100 dark:bg-gray-900 shadow-sm">Date</th>
                                    <th class="sticky top-0 z-20 px-4 py-3 bg-gray-100 dark:bg-gray-900 shadow-sm">Invoice No.</th>
                                    <th class="sticky top-0 z-20 px-4 py-3 bg-gray-100 dark:bg-gray-900 shadow-sm">TIN</th>
                                    <th class="sticky top-0 z-20 px-4 py-3 bg-gray-100 dark:bg-gray-900 shadow-sm">Supplier / Customer Name</th>
                                    <th class="sticky top-0 z-20 px-4 py-3 bg-gray-100 dark:bg-gray-900 shadow-sm text-right">Gross</th>
                                    <th class="sticky top-0 z-20 px-4 py-3 bg-gray-100 dark:bg-gray-900 shadow-sm text-right">Net of VAT</th>
                                    <th class="sticky top-0 z-20 px-4 py-3 bg-gray-100 dark:bg-gray-900 shadow-sm text-right">Output Tax</th>
                                    <th class="sticky top-0 z-20 px-4 py-3 bg-gray-100 dark:bg-gray-900 shadow-sm">Branch</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($sortedSales as $sale)
                                    <tr class="searchable-row hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-700 dark:text-gray-300">{{ $sale['invoice_date'] ?? $sale['date'] ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 font-mono text-gray-900 dark:text-gray-100 font-medium">{{ $sale['invoice_no'] ?? '-' }}</td>
                                        <td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-400">{{ $sale['parsed_tin'] ?? $sale['tin'] ?? '-' }}</td>
                                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-200">{{ $sale['parsed_name'] ?? $sale['customer_name'] ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 text-right font-medium text-gray-800 dark:text-gray-200">₱{{ number_format($sale['gross_amount'] ?? $sale['gross'] ?? 0, 2) }}</td>
                                        <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-300">₱{{ number_format($sale['net_of_vat'] ?? 0, 2) }}</td>
                                        <td class="px-4 py-3 text-right text-blue-600 dark:text-blue-400 font-medium">₱{{ number_format($sale['output_vat'] ?? $sale['output_tax'] ?? 0, 2) }}</td>
                                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $sale['branch'] ?? 'MAIN' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-4 py-6 text-center text-gray-400 dark:text-gray-500">
                                            No sales transactions recorded for this period.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 2: PURCHASES & EXPENSES TABLE -->
                <div id="tabContentPurchases" class="tab-content hidden">
                    <div class="overflow-auto no-scrollbar border border-gray-200 dark:border-gray-700 rounded-lg max-h-[calc(100vh-220px)] relative">
                        <table id="purchasesTable" class="w-full text-sm text-left text-gray-600 dark:text-gray-300 border-collapse">
                            <thead>
                                <tr class="text-xs uppercase bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                                    <th class="sticky top-0 z-20 px-4 py-3 bg-gray-100 dark:bg-gray-900 shadow-sm">Date</th>
                                    <th class="sticky top-0 z-20 px-4 py-3 bg-gray-100 dark:bg-gray-900 shadow-sm">Invoice No.</th>
                                    <th class="sticky top-0 z-20 px-4 py-3 bg-gray-100 dark:bg-gray-900 shadow-sm">TIN</th>
                                    <th class="sticky top-0 z-20 px-4 py-3 bg-gray-100 dark:bg-gray-900 shadow-sm">TP Supplier Name</th>
                                    <th class="sticky top-0 z-20 px-4 py-3 bg-gray-100 dark:bg-gray-900 shadow-sm text-right">Taxable</th>
                                    <th class="sticky top-0 z-20 px-4 py-3 bg-gray-100 dark:bg-gray-900 shadow-sm text-right">Input Tax</th>
                                    <th class="sticky top-0 z-20 px-4 py-3 bg-gray-100 dark:bg-gray-900 shadow-sm text-center">ATC</th>
                                    <th class="sticky top-0 z-20 px-4 py-3 bg-gray-100 dark:bg-gray-900 shadow-sm text-right">2307 (EWT)</th>
                                    <th class="sticky top-0 z-20 px-4 py-3 bg-gray-100 dark:bg-gray-900 shadow-sm text-right">Net Amount</th>
                                    <th class="sticky top-0 z-20 px-4 py-3 bg-gray-100 dark:bg-gray-900 shadow-sm">Particulars</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($sortedPurchases as $purchase)
                                    <tr class="searchable-row hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-700 dark:text-gray-300">{{ $purchase['invoice_date'] ?? $purchase['date'] ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 font-mono text-gray-900 dark:text-gray-100 font-medium">{{ $purchase['invoice_no'] ?? '-' }}</td>
                                        <td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-400">{{ $purchase['parsed_tin'] ?? $purchase['tin'] ?? '-' }}</td>
                                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-200">{{ $purchase['parsed_name'] ?? $purchase['entity_name'] ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 text-right text-gray-800 dark:text-gray-200">₱{{ number_format($purchase['taxable_amount'] ?? 0, 2) }}</td>
                                        <td class="px-4 py-3 text-right text-orange-600 dark:text-orange-400 font-medium">₱{{ number_format($purchase['input_vat'] ?? 0, 2) }}</td>
                                        <td class="px-4 py-3 text-center font-mono text-xs text-gray-600 dark:text-gray-300">{{ $purchase['atc_code'] ?? 'WC158' }}</td>
                                        <td class="px-4 py-3 text-right text-gray-500 dark:text-gray-400">₱{{ number_format($purchase['tax_withheld_2307'] ?? 0, 2) }}</td>
                                        <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-gray-100">₱{{ number_format($purchase['net_amount'] ?? 0, 2) }}</td>
                                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $purchase['particular_category'] ?? $purchase['particular'] ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="px-4 py-6 text-center text-gray-400 dark:text-gray-500">
                                            No purchase records found for this period.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 3: EMPLOYEES & PAYROLL TABLE -->
                <div id="tabContentPayroll" class="tab-content hidden">
                    <div class="overflow-auto no-scrollbar border border-gray-200 dark:border-gray-700 rounded-lg max-h-[calc(100vh-220px)] relative">
                        <table id="payrollTable" class="w-full text-xs text-left text-gray-600 dark:text-gray-300 border-collapse whitespace-nowrap">
                            <thead>
                                <tr class="text-xs uppercase bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                                    <th class="sticky top-0 z-20 px-3 py-3 bg-gray-100 dark:bg-gray-900 shadow-sm">Position</th>
                                    <th class="sticky top-0 z-20 px-3 py-3 bg-gray-100 dark:bg-gray-900 shadow-sm">TIN</th>
                                    <th class="sticky top-0 z-20 px-3 py-3 bg-gray-100 dark:bg-gray-900 shadow-sm">Employee Name</th>
                                    <th class="sticky top-0 z-20 px-3 py-3 bg-gray-100 dark:bg-gray-900 shadow-sm text-right">Above MWE</th>
                                    <th class="sticky top-0 z-20 px-3 py-3 bg-gray-100 dark:bg-gray-900 shadow-sm text-right">MWE</th>
                                    <th class="sticky top-0 z-20 px-3 py-3 bg-gray-100 dark:bg-gray-900 shadow-sm text-right">SSS (EE)</th>
                                    <th class="sticky top-0 z-20 px-3 py-3 bg-gray-100 dark:bg-gray-900 shadow-sm text-right">PHIC (EE)</th>
                                    <th class="sticky top-0 z-20 px-3 py-3 bg-gray-100 dark:bg-gray-900 shadow-sm text-right">HDMF (EE)</th>
                                    <th class="sticky top-0 z-20 px-3 py-3 bg-gray-100 dark:bg-gray-900 shadow-sm text-right">Holiday Pay</th>
                                    <th class="sticky top-0 z-20 px-3 py-3 bg-gray-100 dark:bg-gray-900 shadow-sm text-right font-bold text-gray-900 dark:text-white">Gross</th>
                                    <th class="sticky top-0 z-20 px-3 py-3 bg-gray-100 dark:bg-gray-900 shadow-sm text-right">SSS (ER)</th>
                                    <th class="sticky top-0 z-20 px-3 py-3 bg-gray-100 dark:bg-gray-900 shadow-sm text-right">PHIC (ER)</th>
                                    <th class="sticky top-0 z-20 px-3 py-3 bg-gray-100 dark:bg-gray-900 shadow-sm text-right">HDMF (ER)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($payroll ?? [] as $item)
                                    <tr class="searchable-row hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-3 py-2.5 font-medium text-gray-800 dark:text-gray-200">{{ $item['position'] ?? '-' }}</td>
                                        <td class="px-3 py-2.5 font-mono text-gray-500 dark:text-gray-400">{{ $item['tin'] ?? '-' }}</td>
                                        <td class="px-3 py-2.5 font-semibold text-gray-900 dark:text-gray-100">{{ $item['full_name'] ?? 'N/A' }}</td>
                                        <td class="px-3 py-2.5 text-right font-mono">{{ !empty($item['above_mwe']) ? '₱'.number_format($item['above_mwe'], 2) : '-' }}</td>
                                        <td class="px-3 py-2.5 text-right font-mono">{{ !empty($item['mwe']) ? '₱'.number_format($item['mwe'], 2) : '-' }}</td>
                                        <td class="px-3 py-2.5 text-right font-mono">₱{{ number_format($item['sss_ee'] ?? 0, 2) }}</td>
                                        <td class="px-3 py-2.5 text-right font-mono">₱{{ number_format($item['phic_ee'] ?? 0, 2) }}</td>
                                        <td class="px-3 py-2.5 text-right font-mono">₱{{ number_format($item['hdmf_ee'] ?? 0, 2) }}</td>
                                        <td class="px-3 py-2.5 text-right font-mono">₱{{ number_format($item['holiday_pay'] ?? 0, 2) }}</td>
                                        <td class="px-3 py-2.5 text-right font-mono font-bold text-green-600 dark:text-green-400">₱{{ number_format($item['gross_pay'] ?? 0, 2) }}</td>
                                        <td class="px-3 py-2.5 text-right font-mono text-gray-500">₱{{ number_format($item['sss_er'] ?? 0, 2) }}</td>
                                        <td class="px-3 py-2.5 text-right font-mono text-gray-500">₱{{ number_format($item['phic_er'] ?? 0, 2) }}</td>
                                        <td class="px-3 py-2.5 text-right font-mono text-gray-500">₱{{ number_format($item['hdmf_er'] ?? 0, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="13" class="px-4 py-6 text-center text-gray-400 dark:text-gray-500">
                                            No payroll entries recorded for this period.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ROW LIMIT SELECTION CONTROLS BELOW TABLE -->
                <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700 flex flex-wrap items-center justify-between gap-3 text-xs text-gray-600 dark:text-gray-400 font-medium">
                    <div class="flex items-center gap-2">
                        <label for="rowLimitSelect" class="uppercase font-semibold">Rows Per Page:</label>
                        <select id="rowLimitSelect" onchange="updateRowLimit(this.value)" class="border border-gray-300 dark:border-gray-600 rounded px-2 py-1 bg-white dark:bg-gray-700 text-gray-800 dark:text-white text-xs font-semibold focus:ring-2 focus:ring-blue-500">
                            <option value="50">50 rows</option>
                            <option value="100" selected>100 rows</option>
                            <option value="200">200 rows</option>
                            <option value="all">All rows</option>
                        </select>
                    </div>
                    <div id="rowCountIndicator" class="font-mono text-gray-500 dark:text-gray-400">
                        Showing rows...
                    </div>
                </div>

            </div>

        @else
            <div class="bg-yellow-50 dark:bg-gray-800 border border-yellow-200 dark:border-yellow-700/60 text-yellow-800 dark:text-yellow-300 p-6 rounded-lg text-center">
                <p class="font-semibold text-lg">No Client Selected</p>
                <p class="text-sm mt-1 text-gray-600 dark:text-gray-400">Please choose a managed client from the top dropdown to view their financial and tax summaries.</p>
            </div>
        @endif
    </main>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        let currentTab = 'sales';
        let currentRowLimit = 100;

        // Listen for Alt + Wheel to pan table horizontally
        document.addEventListener('wheel', function(e) {
            if (e.altKey) {
                const overflowContainer = e.target.closest('.overflow-auto');
                if (overflowContainer) {
                    e.preventDefault();
                    overflowContainer.scrollLeft += e.deltaY;
                }
            }
        }, { passive: false });

        document.addEventListener("DOMContentLoaded", function() {
            filterTable();
        });

        function showLoading() {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) {
                overlay.classList.remove('hidden');
                overlay.classList.add('flex');
            }
        }

        function submitFilterForm() {
            showLoading();
            document.getElementById('clientContextForm').submit();
        }

        function openLogoutModal() {
            const modal = document.getElementById('logoutModal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        }

        function closeLogoutModal() {
            const modal = document.getElementById('logoutModal');
            if (modal) {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
            }
        }

        function switchTab(tab) {
            currentTab = tab;
            const salesContent = document.getElementById('tabContentSales');
            const purchasesContent = document.getElementById('tabContentPurchases');
            const payrollContent = document.getElementById('tabContentPayroll');

            const salesBtn = document.getElementById('tabBtnSales');
            const purchasesBtn = document.getElementById('tabBtnPurchases');
            const payrollBtn = document.getElementById('tabBtnPayroll');

            salesContent.classList.add('hidden');
            purchasesContent.classList.add('hidden');
            payrollContent.classList.add('hidden');

            const inactiveClass = "px-4 py-2 text-sm font-semibold rounded-md transition-all duration-150 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white";
            salesBtn.className = inactiveClass;
            purchasesBtn.className = inactiveClass;
            payrollBtn.className = inactiveClass;

            if (tab === 'sales') {
                salesContent.classList.remove('hidden');
                salesBtn.className = "px-4 py-2 text-sm font-bold rounded-md transition-all duration-150 bg-white dark:bg-gray-800 text-blue-600 dark:text-blue-400 shadow";
            } else if (tab === 'purchases') {
                purchasesContent.classList.remove('hidden');
                purchasesBtn.className = "px-4 py-2 text-sm font-bold rounded-md transition-all duration-150 bg-white dark:bg-gray-800 text-orange-600 dark:text-orange-400 shadow";
            } else if (tab === 'payroll') {
                payrollContent.classList.remove('hidden');
                payrollBtn.className = "px-4 py-2 text-sm font-bold rounded-md transition-all duration-150 bg-white dark:bg-gray-800 text-green-600 dark:text-green-400 shadow";
            }

            filterTable();
        }

        function updateRowLimit(limit) {
            currentRowLimit = limit === 'all' ? 'all' : parseInt(limit, 10);
            filterTable();
        }

        function filterTable() {
            const input = document.getElementById('tableSearchInput').value.toLowerCase().trim();
            let activeTableId = 'salesTable';

            if (currentTab === 'purchases') activeTableId = 'purchasesTable';
            else if (currentTab === 'payroll') activeTableId = 'payrollTable';

            const table = document.getElementById(activeTableId);
            if (!table) return;

            const rows = table.getElementsByClassName('searchable-row');
            let visibleCount = 0;
            let totalMatching = 0;

            for (let i = 0; i < rows.length; i++) {
                const text = rows[i].textContent.toLowerCase();
                const matchesSearch = text.includes(input);

                if (matchesSearch) {
                    totalMatching++;
                    if (currentRowLimit === 'all' || visibleCount < currentRowLimit) {
                        rows[i].style.display = "";
                        visibleCount++;
                    } else {
                        rows[i].style.display = "none";
                    }
                } else {
                    rows[i].style.display = "none";
                }
            }

            // Update row count indicator text below table
            const indicator = document.getElementById('rowCountIndicator');
            if (indicator) {
                indicator.textContent = `Showing ${visibleCount} of ${totalMatching} entries`;
            }
        }

        function toggleTheme() {
            const html = document.documentElement;
            if (html.classList.contains('dark')) {
                html.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                html.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
        }

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
                submitFilterForm();
            }
        }
    </script>
</body>
</html>