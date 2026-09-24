<?php

namespace App\Http\Controllers;

use App\Services\StatistikService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(StatistikService $statistikService): Response
    {
        $dashboardData = $statistikService->getDashboardSummary();

        return Inertia::render('Dashboard', [
            'dashboardData' => $dashboardData,
        ]);
    }
}

