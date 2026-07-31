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
                    <option value="{{ $client['id'] }}" {{ (request('client_id')) == $client['id'] || session('active_client_id') == $client['id'] ? 'selected' : '' }}>
                        {{ $client['name'] }} (TIN: {{ $client['tin'] }})
                    </option>
                    @endforeach
                </select>
            </form>
        </header>

        @if($clients && isset($summary))
            
    </body>
</html>
