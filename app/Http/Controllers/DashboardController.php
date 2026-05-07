<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\MaintenanceLog;
use App\Models\Location;
use App\Models\Department;
use App\Models\Category;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class DashboardController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('role:admin|staff'),
        ];
    }
    /**
     * Display the main dashboard with statistics and chart data.
     */
    public function index()
    {
        $totalAssets = Asset::count();
        $availableAssets = Asset::where('status', 'available')->count();
        $inUseAssets = Asset::where('status', 'in_use')->count();
        $maintenanceAssets = Asset::where('status', 'maintenance')->count();
        $damagedAssets = Asset::where('status', 'damaged')->count();
        $totalLocations = Location::count();
        $totalValue = Asset::sum('purchase_price');

        $recentMovements = AssetMovement::with(['asset', 'toLocation', 'user'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();
        $recentMaintenance = MaintenanceLog::with(['asset', 'user'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();
        $recentLogs = ActivityLog::with('user')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // --- CHART DATA ---

        // 1. Assets by Status (for Doughnut chart)
        $assetsByStatus = [
            ['label' => 'Tersedia', 'value' => $availableAssets, 'color' => '#10b981'],
            ['label' => 'Digunakan', 'value' => $inUseAssets, 'color' => '#3b82f6'],
            ['label' => 'Perbaikan', 'value' => $maintenanceAssets, 'color' => '#f59e0b'],
            ['label' => 'Rusak', 'value' => $damagedAssets, 'color' => '#ef4444'],
        ];

        // 2. Assets by Category (for Bar chart)
        $categories = Category::withCount(['subcategories as assets_count' => function ($query) {
            // count assets through subcategories
        }])->get();

        // More accurate: count assets grouped by category through subcategory
        $assetsByCategory = DB::table('assets')
            ->join('subcategories', 'assets.subcategory_id', '=', 'subcategories.id')
            ->join('categories', 'subcategories.category_id', '=', 'categories.id')
            ->select('categories.name', DB::raw('count(*) as total'))
            ->whereNull('assets.deleted_at')
            ->groupBy('categories.name')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn($row) => ['label' => $row->name, 'value' => $row->total])
            ->values()
            ->toArray();

        // 3. Assets by Department (for Horizontal Bar chart)
        $assetsByDepartment = DB::table('assets')
            ->leftJoin('departments', 'assets.department_id', '=', 'departments.id')
            ->select(DB::raw("COALESCE(departments.name, 'Tidak Ada Dept.') as name"), DB::raw('count(*) as total'))
            ->whereNull('assets.deleted_at')
            ->groupBy(DB::raw("COALESCE(departments.name, 'Tidak Ada Dept.')"))
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn($row) => ['label' => $row->name, 'value' => $row->total])
            ->values()
            ->toArray();

        // 4. Monthly Trend (last 12 months - for Line chart)
        $twelveMonthsAgo = now()->subMonths(11)->startOfMonth();
        $allAssets = Asset::where('created_at', '>=', $twelveMonthsAgo)
            ->get(['created_at']);

        $monthlyTrend = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $key = $date->format('Y-m');
            $label = $date->translatedFormat('M Y');
            $count = $allAssets->filter(fn($a) => $a->created_at->format('Y-m') === $key)->count();
            $monthlyTrend[] = ['month' => $label, 'count' => $count];
        }

        return Inertia::render('Dashboard', [
            'stats' => [
                'total' => $totalAssets,
                'available' => $availableAssets,
                'in_use' => $inUseAssets,
                'maintenance' => $maintenanceAssets,
                'damaged' => $damagedAssets,
                'locations' => $totalLocations,
                'totalValue' => $totalValue,
            ],
            'chartData' => [
                'assetsByStatus' => $assetsByStatus,
                'assetsByCategory' => $assetsByCategory,
                'assetsByDepartment' => $assetsByDepartment,
                'monthlyTrend' => $monthlyTrend,
            ],
            'recentMovements' => $recentMovements,
            'recentMaintenance' => $recentMaintenance,
            'recentLogs' => $recentLogs,
        ]);
    }
}
