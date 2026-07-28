<?php

namespace App\Http\Controllers;

use App\Services\SupabaseAuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request, SupabaseAuthService $authService)
    {
        return $authService->login($request);
    }

    public function logout(Request $request, SupabaseAuthService $authService)
    {
        return $authService->logout($request);
    }
}