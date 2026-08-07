<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>VAT Summary {{ $year }}</title>
    <style>
        @page { size: A4 landscape; margin: 8mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 8px; color: #000; }
        .title { font-size: 11px; font-weight: bold; margin-bottom: 6px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        th, td { border: 1px solid #777; padding: 4px 3px; text-align: right; }
        th { background-color: #f3f4f6; font-weight: bold; text-transform: uppercase; text-align: center; font-size: 8px; }
        td.text-left { text-align: left; font-weight: bold; }
        
        .row-carried { font-style: italic; background-color: #fafafa; }
        .row-quarter { background-color: #e5e7eb; font-weight: bold; border-top: 1.5px solid #000; border-bottom: 1.5px solid #000; }
        .highlight-yellow { background-color: #fef08a; font-weight: bold; }
    </style>
</head>
<body>

    <div class="title">VAT Summary</div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%; text-align: left;"></th>
                <th>GROSS TOTAL</th>
                @foreach($branches as $branch)
                    <th>{{ $branch['branch_name'] ?? $branch['name'] }}</th>
                @endforeach
                <th>Non-VAT/Exempt</th>
                <th>Net of VAT</th>
                <th>VAT Payable</th>
            </tr>
        </thead>
        <tbody>
            <!-- Carried Over Input Tax Row -->
            <tr class="row-carried">
                <td class="text-left" colspan="{{ 4 + count($branches) }}">CARRIED OVER INPUT TAX</td>
                <td class="highlight-yellow">({{ number_format($carriedOverInputTax, 2) }})</td>
            </tr>

            @foreach($matrix as $q)
                @foreach($q['rows'] as $row)
                    <tr>
                        <td class="text-left">{{ $row['month'] }}</td>
                        <td>{{ number_format($row['gross_total'], 2) }}</td>
                        @foreach($branches as $branch)
                            <td>{{ number_format($row['branches'][$branch['id']] ?? 0, 2) }}</td>
                        @endforeach
                        <td>{{ number_format($row['non_vat'], 2) }}</td>
                        <td>{{ number_format($row['net_of_vat'], 2) }}</td>
                        <td>{{ number_format($row['vat_payable'], 2) }}</td>
                    </tr>
                @endforeach

                <!-- Quarter Subtotal Row -->
                <tr class="row-quarter">
                    <td class="text-left">{{ $q['quarter'] }}</td>
                    <td>{{ number_format($q['q_gross'], 2) }}</td>
                    @foreach($branches as $branch)
                        <td>{{ number_format($q['q_branches'][$branch['id']] ?? 0, 2) }}</td>
                    @endforeach
                    <td>{{ number_format($q['q_non_vat'], 2) }}</td>
                    <td>{{ number_format($q['q_net_vat'], 2) }}</td>
                    <td class="highlight-yellow">{{ number_format($q['q_vat_payable'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>