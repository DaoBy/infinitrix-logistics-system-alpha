<?php

namespace App\Http\Controllers\Dashboard;
use App\Http\Controllers\Controller; 
use Inertia\Inertia;
use Inertia\Response;

class CollectorDashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Collector/CollectorDash');
    }
}
