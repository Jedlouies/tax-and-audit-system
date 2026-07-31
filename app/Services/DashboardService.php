<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class DashboardService {
    protected string $url;
    protected string $key;

    public function __construct()
    {
        $this->url = config('services.supabase.url');
        $this->key = config('services.supabase.key');
    }

    public function index(Request $request) {
        
        try {
            $clientsResponse = Http::withoutVerifying()
            ->withHeaders([
                'apikey' => $this->key,
                'Authorization' => 'Bearer ' . $this->key,
            ])->get("{$this->url}/rest/v1/users", [
                'select' => '*',
                'role' => 'eq.' . "client",
            ]);

            $clients = $clientsResponse->json();
        } catch (\Exception $e) {
            Log::warning("Failed to Fetch Clients: " . $e->getMessage());
        }
        

        return view('Admin.dashboard', compact('clients'));
    }
}