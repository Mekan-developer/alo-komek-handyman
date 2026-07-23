<?php

namespace App\Http\Controllers;

use App\Repositories\DashboardRepository;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardRepository $repository) {}

    public function index(): Response
    {
        return Inertia::render('Dashboard', [
            'stats' => $this->repository->stats(),
            'ordersByStatus' => $this->repository->ordersByStatus(),
            'recentOrders' => $this->repository->recentOrders(),
        ]);
    }
}
