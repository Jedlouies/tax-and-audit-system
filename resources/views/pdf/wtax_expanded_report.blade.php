<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Withholding Tax Summary TY {{ $year }}</title>
    <style>
        @page { size: A4 landscape; margin: 12mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #111; }
        .header { margin-bottom: 12px; }
        .header h3 { font-size: 11px; margin: 0; font-weight: normal; }
        .header h2 { font-size: 14px; margin: 2px 0; font-weight: bold; text-transform: uppercase; }
        .header p { font-size: 9px; color: #555; margin: 2px 0 0 0; }

        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #999; padding: 5px 6px; text-align: right; }
        th { background-color: #f3f4f6; font-weight: bold; text-transform: uppercase; text-align: center; }
        td.text-left { text-align: left; font-weight: bold; }

        .row-carried { background-color: #fafafa; font-style: italic; }
        .row-quarter { background-color: #e5e7eb; font-weight: bold; border-top: 1.5px solid #000; border-bottom: 1.5px solid #000; }
        .highlight-yellow { background-color: #fef08a; font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <h3>TY {{ $year }}</h3>
        <h2>Withholding Tax Summary</h2>
        <p><strong>Taxpayer:</strong> {{ $user['name'] ?? 'N/A' }} | <strong>TIN:</strong> {{ $user['tin'] ?? 'N/A' }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 8%; text-align: left;">Month</th>
                @foreach($atcColumns as $atc)
                    <th>{{ $atc }}</th>
                @endforeach
                <th>w/tax Payable</th>
                <th>TaxPayable</th>
            </tr>
        </thead>
        <tbody>
            <!-- Carried Over Payment Row -->
            <tr class="row-carried">
                <td class="text-left">carried over payment</td>
                <td colspan="{{ count($atcColumns) }}"></td>
                <td>({{ number_format($carriedOverPayment, 2) }})</td>
                <td></td>
            </tr>

            @foreach($matrix as $q)
                @foreach($q['rows'] as $row)
                    <tr>
                        <td class="text-left">{{ $row['month'] }}</td>
                        @foreach($atcColumns as $atc)
                            <td>{{ number_format($row['atc_totals'][$atc] ?? 0, 2) }}</td>
                        @endforeach
                        <td>{{ number_format($row['wtax_payable'], 2) }}</td>
                        <td>{{ number_format($row['wtax_payable'], 2) }}</td>
                    </tr>
                @endforeach

                <!-- Quarter Summary Row -->
                <tr class="row-quarter">
                    <td class="text-left">{{ $q['quarter'] }}</td>
                    @foreach($atcColumns as $atc)
                        <td>{{ number_format($q['q_atc_totals'][$atc] ?? 0, 2) }}</td>
                    @endforeach
                    <td class="highlight-yellow">{{ number_format($q['q_wtax_payable'], 2) }}</td>
                    <td></td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>