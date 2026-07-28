<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SupabaseAuthService
{
    protected string $url;
    protected string $key;

    public function __construct()
    {
        $this->url = rtrim(config('services.supabase.url') ?? '', '/');
        $this->key = config('services.supabase.key') ?? '';
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]); 

        try {
            // 1. Authenticate against Supabase Auth API
            $response = Http::withHeaders([
                'apikey' => $this->key,
                'Content-Type' => 'application/json',
            ])->post("{$this->url}/auth/v1/token?grant_type=password", [
                'email' => $credentials['email'],
                'password' => $credentials['password'],
            ]);

            if ($response->failed()) {
                return back()->withErrors([
                    'email' => $response->json('error_description') ?? $response->json('msg') ?? 'Login failed.'
                ])->onlyInput('email');
            }

            $userResponse = Http::withHeaders([
                'apikey' => $this->key,
                'Authorization' => 'Bearer ' . $this->key,
            ])->get("{$this->url}/rest/v1/users", [
                'email' => "eq." . $credentials['email'],
                'select' => '*',
            ]);

            $supabaseUsers = $userResponse->json();

            if (empty($supabaseUsers)) {
                return back()->withErrors(['email' => 'User profile not found in database.']);
            }

            $supabaseUserData = $supabaseUsers[0];

            $localUser = User::firstOrCreate(
                ['email' => $supabaseUserData['email']],
                [
                    'name' => $supabaseUserData['name'] ?? $supabaseUserData['email'],
                    'password' => bcrypt(\Illuminate\Support\Str::random(16)),
                ]
            );

            Auth::login($localUser);

            session(['userData' => $supabaseUserData]);

            $request->session()->regenerate();

            return redirect()->intended('/dashboard');

        } catch (Exception $e) {
            Log::error('Login Error: ' . $e->getMessage());
            return back()->withErrors([
                'email' => 'Authentication Failed: ' . $e->getMessage(), 
            ])->onlyInput('email');
        }
    }

    public function logout(Request $request) 
    {
        try {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/');
        } catch (Exception $e) {
            Log::error('Supabase Logout Error: ' . $e->getMessage());
            return redirect('/');
        }
    }
}