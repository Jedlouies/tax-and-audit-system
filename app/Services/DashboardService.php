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

            $clients = [];
            $sales = [];
            $summary = null;

            $activeClientId = $request->get('client_id', session('active_client_id'));

            if($activeClientId) {
                session(['active_client_id' => $activeClientId]);
            } else {
                session()->forget('active_client_id');
            }

        try 
        {    
            $clientsResponse = Http::withoutVerifying()
            ->withHeaders([
                'apikey' => $this->key,
                'Authorization' => 'Bearer ' . $this->key,
            ])->get("{$this->url}/rest/v1/users", [
                'select' => '*',
                'role' => 'eq.client',
            ]);

            if($clientsResponse->successful()){
                $clients = $clientsResponse->json();
            }
            
            if ($activeClientId) {
                $salesResponse = Http::withoutVerifying()
                ->withHeaders([
                    'apikey' => $this->key,
                    'Authorization' => 'Bearer ' . $this->key,
                ])->get("{$this->url}/rest/v1/sales", [
                    'select' => '*',
                    'user_id' => 'eq.' . $activeClientId,
                ]);
                
                if ($salesResponse->successful()) {
                    $sales = $salesResponse->json();
                }
                
                    $salesCollection = collect($sales);
                    $summary = [
                        'totalSales' => $salesCollection->sum('gross_amount'),
                        'totalNet' => $salesCollection->sum('net_of_vat'),
                        'outputVat' => $salesCollection->sum('output_vat'),
                    ];
            }
            

        } catch (\Exception $e) {
            Log::warning("Failed to Fetch Data: " . $e->getMessage());
        }

        
        


        return view('Admin.dashboard', compact('clients', 'summary', 'activeClientId'));
    }
}