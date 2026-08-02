<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>TAS | Dashboard</title>
        
    </head>
    <body >
        <header>
            <div>
                <h2>Admin Portal</h2>
            </div>
            <form action="{{route('dashboard')}}" method="GET" id=clientContextForm>
                <label for="client_select">Clients:</label>
                <select name="client_id" id="client_select" onchange="this.form.submit()">
                    <option value="">-- All Client Overview --</option>
                    @foreach($clients ?? [] as $client)
                    <option value="{{ $client['id'] }}" {{ (string) (request('client_id')) == (string) $client['id'] || (string) ($activeClientId ?? '') === (string) $client['id'] ? 'selected' : '' }}>
                        {{ $client['name'] }} (TIN: {{ $client['tin'] }})
                    </option>
                    @endforeach
                </select>
            </form>
        </header>

        @if(!empty($activeClientId) && isset($summary))
            <div>
                <p>Gross Sales: </p>
                <p>₱{{number_format($summary['totalSales'], 2)}}</p>
            </div>
            <div>
                <p>Net of VAT: </p>
                <p>₱{{number_format($summary['totalNet'], 2)}}</p>
            </div>
            <div>
                <p>Output VAT:</p>
                <p>₱{{number_format($summary['outputVat'], 2)}}</p>
            </div>
        @endif
    </body>
</html>
