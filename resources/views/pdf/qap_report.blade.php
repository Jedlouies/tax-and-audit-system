<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>BIR QAP Report (1601-EQ)</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 7px; color: #111; margin: 0; padding: 0; }
        .header { margin-bottom: 10px; }
        .header h3 { margin: 0; font-size: 10px; text-transform: uppercase; }
        .header p { margin: 2px 0; font-size: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; table-layout: fixed; }
        th, td { border: 1px solid #666; padding: 3px 2px; text-align: left; word-wrap: break-word; }
        th { background-color: #f2f2f2; font-size: 6.5px; text-align: center; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <p class="font-bold">Attachment to BIR Form 1601-EQ</p>
        <h3>QUARTERLY ALPHABETICAL LIST OF PAYEES SUBJECTED TO EXPANDED WITHHOLDING TAX</h3>
        <p>FOR THE QUARTER ENDING {{ $quarterName }}</p>
        <br>
        <p><strong>TIN:</strong> {{ $user['tin'] ?? '103313479-0000' }}</p>
        <p><strong>WITHHOLDING AGENT'S NAME:</strong> {{ strtoupper($user['name'] ?? 'GO, RENY ONG') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2" style="width: 3%;">SEQ</th>
                <th rowspan="2" style="width: 10%;">TIN</th>
                <th rowspan="2" style="width: 15%;">CORPORATION (Registered Name)</th>
                <th rowspan="2" style="width: 15%;">INDIVIDUAL (Last Name, First Name)</th>
                <th rowspan="2" style="width: 5%;">ATC</th>
                <th rowspan="2" style="width: 4%;">RATE</th>
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
            @forelse($rows as $index => $row)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $row['tin'] }}</td>
                    <td class="font-bold">{{ $row['corp_name'] }}</td>
                    <td class="font-bold">{{ $row['ind_name'] }}</td>
                    <td class="text-center">{{ $row['atc'] }}</td>
                    <td class="text-center">{{ $row['tax_rate'] }}</td>
                    <td class="text-right">{{ number_format($row['m1_amt'], 2) }}</td>
                    <td class="text-right">{{ number_format($row['m1_tax'], 2) }}</td>
                    <td class="text-right">{{ number_format($row['m2_amt'], 2) }}</td>
                    <td class="text-right">{{ number_format($row['m2_tax'], 2) }}</td>
                    <td class="text-right">{{ number_format($row['m3_amt'], 2) }}</td>
                    <td class="text-right">{{ number_format($row['m3_tax'], 2) }}</td>
                    <td class="text-right font-bold">{{ number_format($row['quarter_total_amt'], 2) }}</td>
                    <td class="text-right font-bold">{{ number_format($row['quarter_total_tax'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="14" class="text-center" style="padding: 10px;">
                        No withholding records found for Quarter {{ $quarter }}.
                    </td>
                </tr>
            @endforelse

            @if(count($rows) > 0)
                <tr class="font-bold bg-gray-100">
                    <td colspan="12" class="text-right">Grand Total:</td>
                    <td class="text-right">₱{{ number_format($grandTotalAmt, 2) }}</td>
                    <td class="text-right">₱{{ number_format($grandTotalTax, 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <p style="margin-top: 15px; text-align: center; font-weight: bold;">END OF REPORT</p>

</body>
</html>