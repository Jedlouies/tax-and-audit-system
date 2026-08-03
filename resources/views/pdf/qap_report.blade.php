<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>BIR QAP Report (1601-EQ)</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 6.5px; color: #111; margin: 0; padding: 0; }
        .header { margin-bottom: 8px; }
        .header h3 { margin: 0; font-size: 9.5px; text-transform: uppercase; }
        .header p { margin: 1px 0; font-size: 7.5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 4px; table-layout: fixed; }
        th, td { border: 1px solid #555; padding: 3px 2px; text-align: left; word-wrap: break-word; }
        th { background-color: #f2f2f2; font-size: 6px; text-align: center; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .font-mono { font-family: monospace; }
    </style>
</head>
<body>

    <div class="header">
        <p class="font-bold">Attachment to BIR Form 1601-EQ</p>
        <h3>QUARTERLY ALPHABETICAL LIST OF PAYEES SUBJECTED TO EXPANDED WITHHOLDING TAX</h3>
        <p>FOR THE QUARTER ENDING {{ $quarterName }} (QUARTER {{ $quarter }})</p>
        <br>
        <p><strong>TIN:</strong> {{ $user['tin'] ?? '103-313-479-0000' }}</p>
        <p><strong>WITHHOLDING AGENT'S NAME:</strong> {{ strtoupper($user['name'] ?? 'GO, RENY ONG') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2" style="width: 3%;">SEQ</th>
                <th rowspan="2" style="width: 10%;">TIN</th>
                <th rowspan="2" style="width: 14%;">CORPORATION (Registered Name)</th>
                <th rowspan="2" style="width: 14%;">INDIVIDUAL (Last Name, First Name)</th>
                <th rowspan="2" style="width: 5%;">ATC</th>
                <th rowspan="2" style="width: 4%;">TAX RATE</th>
                <th colspan="2">1ST MONTH</th>
                <th colspan="2">2ND MONTH</th>
                <th colspan="2">3RD MONTH</th>
                <th colspan="2">QUARTER TOTAL</th>
            </tr>
            <tr>
                <th style="width: 6%;">INCOME</th>
                <th style="width: 5%;">TAX</th>
                <th style="width: 6%;">INCOME</th>
                <th style="width: 5%;">TAX</th>
                <th style="width: 6%;">INCOME</th>
                <th style="width: 5%;">TAX</th>
                <th style="width: 8%;">INCOME</th>
                <th style="width: 7%;">TAX</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totM1Amt = $totM1Tax = 0;
                $totM2Amt = $totM2Tax = 0;
                $totM3Amt = $totM3Tax = 0;
            @endphp

            @forelse($rows as $index => $row)
                @php
                    $totM1Amt += $row['m1_amt'] ?? 0; $totM1Tax += $row['m1_tax'] ?? 0;
                    $totM2Amt += $row['m2_amt'] ?? 0; $totM2Tax += $row['m2_tax'] ?? 0;
                    $totM3Amt += $row['m3_amt'] ?? 0; $totM3Tax += $row['m3_tax'] ?? 0;
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="font-mono">{{ $row['tin'] ?? '-' }}</td>
                    <td class="font-bold">{{ $row['corp_name'] ?? '' }}</td>
                    <td class="font-bold">{{ $row['ind_name'] ?? '' }}</td>
                    <td class="text-center">{{ $row['atc'] ?? 'WC158' }}</td>
                    <td class="text-center font-bold">{{ $row['tax_rate'] ?? '1%' }}</td>
                    
                    <!-- Month 1 -->
                    <td class="text-right">{{ number_format($row['m1_amt'] ?? 0, 2) }}</td>
                    <td class="text-right">{{ number_format($row['m1_tax'] ?? 0, 2) }}</td>
                    
                    <!-- Month 2 -->
                    <td class="text-right">{{ number_format($row['m2_amt'] ?? 0, 2) }}</td>
                    <td class="text-right">{{ number_format($row['m2_tax'] ?? 0, 2) }}</td>
                    
                    <!-- Month 3 -->
                    <td class="text-right">{{ number_format($row['m3_amt'] ?? 0, 2) }}</td>
                    <td class="text-right">{{ number_format($row['m3_tax'] ?? 0, 2) }}</td>
                    
                    <!-- Quarter Total -->
                    <td class="text-right font-bold">{{ number_format($row['quarter_total_amt'] ?? 0, 2) }}</td>
                    <td class="text-right font-bold">{{ number_format($row['quarter_total_tax'] ?? 0, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="14" class="text-center" style="padding: 10px;">
                        No withholding records found for Quarter {{ $quarter }}.
                    </td>
                </tr>
            @endforelse

            @if(count($rows) > 0)
                <tr class="font-bold" style="background-color: #eaeaea;">
                    <td colspan="6" class="text-right">Quarter {{ $quarter }} Totals:</td>
                    <td class="text-right">{{ number_format($totM1Amt, 2) }}</td>
                    <td class="text-right">{{ number_format($totM1Tax, 2) }}</td>
                    <td class="text-right">{{ number_format($totM2Amt, 2) }}</td>
                    <td class="text-right">{{ number_format($totM2Tax, 2) }}</td>
                    <td class="text-right">{{ number_format($totM3Amt, 2) }}</td>
                    <td class="text-right">{{ number_format($totM3Tax, 2) }}</td>
                    <td class="text-right">₱{{ number_format($grandTotalAmt ?? 0, 2) }}</td>
                    <td class="text-right">₱{{ number_format($grandTotalTax ?? 0, 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <p style="margin-top: 15px; text-align: center; font-weight: bold;">END OF REPORT</p>

</body>
</html>