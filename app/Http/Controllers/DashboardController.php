<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller 
{
    public function index(Request $request, DashboardService $getDashboardData) {
        return $getDashboardData->index($request);
    }
}