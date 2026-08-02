<?php

namespace App\Http\Controllers;

use App\Services\PdfReportService;
use App\Services\QapPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ReportController extends Controller
{
    protected string $url;
    protected string $key;

    public function __construct()
    {
        $this->url = config('services.supabase.url');
        $this->key = config('services.supabase.key');
    }

    public function exportVatPdf(Request $request, PdfReportService $pdfService)
    {
        $clientId = $request->get('client_id');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        if (!$clientId) {
            return redirect()->back()->with('error', 'Please select a client.');
        }

        return $pdfService->generateVatReport($clientId, $startDate, $endDate);
    }

    public function exportQapPdf(Request $request, QapPdfService $qapPdfService)
    {
        $clientId = $request->get('client_id', session('active_client_id'));
        $quarter = (int) $request->get('quarter', ceil(now()->month / 3));
        $year = (int) $request->get('year', now()->year);

        if (!$clientId) {
            return redirect()->back()->with('error', 'Please select a client.');
        }

        return $qapPdfService->generatePdf($clientId, $quarter, $year);
    }
}