<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\MaintenanceLog;
use App\Models\Location;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Display the main dashboard with statistics.
     */
    public function index()
    {
        $totalAssets = Asset::count();
        $availableAssets = Asset::where('status', 'available')->count();
        $inUseAssets = Asset::where('status', 'in_use')->count();
        $maintenanceAssets = Asset::where('status', 'maintenance')->count();
        $damagedAssets = Asset::where('status', 'damaged')->count();
        $totalLocations = Location::count();
        $recentMovements = AssetMovement::with(['asset', 'toLocation', 'user'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();
        $recentMaintenance = MaintenanceLog::with(['asset', 'user'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => [
                'total' => $totalAssets,
                'available' => $availableAssets,
                'in_use' => $inUseAssets,
                'maintenance' => $maintenanceAssets,
                'damaged' => $damagedAssets,
                'locations' => $totalLocations,
            ],
            'recentMovements' => $recentMovements,
            'recentMaintenance' => $recentMaintenance,
        ]);
    }
}
