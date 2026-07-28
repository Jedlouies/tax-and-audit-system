<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SupabaseService
{
    protected string $url;
    protected string $key;

    public function __construct()
    {
        $this->url = config('services.supabase.url');
        $this->key = config('services.supabase.key');
    }

    public function getTable(string $table, array $queryParams = [])
    {
        $response = Http::withHeaders(
            [
              'apikey' => $this->key,
              'Authorization' => $this->key,  
            ]
        )->get("{$this->url}/rest/v1/{$table}", $queryParams);

        return $response->json();
    }
    
}