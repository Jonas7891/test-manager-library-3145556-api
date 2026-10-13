<?php

namespace App\Adapters\Inbound\Http\Controllers;

use App\Adapters\Inbound\Http\Presenter;
use App\Application\Services\MostBorrowedReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** HU-10 (solo admin) */
final class ReportController
{
    public function mostBorrowed(Request $request, MostBorrowedReportService $service): JsonResponse
    {
        $d = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d'],
        ]);

        return Presenter::json($service->report($d['from'], $d['to']));
    }
}
