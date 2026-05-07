<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceLog;
use App\Models\Asset;
use App\Models\AssetMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class MaintenanceLogController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:view activity', only: ['index']),
            new Middleware('can:create activity', only: ['store']),
            new Middleware('can:edit activity', only: ['update', 'complete']),
            new Middleware('can:delete activity', only: ['destroy', 'bulkDelete']),
        ];
    }
    /**
     * Display a listing of maintenance logs.
     */
    public function index(Request $request)
    {
        $query = MaintenanceLog::with(['asset', 'user']);

        // Search logic
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('asset', function ($sq) use ($search) {
                    $sq->where('name', 'like', "%{$search}%")
                       ->orWhere('asset_code', 'like', "%{$search}%");
                })
                ->orWhere('technician', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Sorting logic
        $sort = $request->input('sort', 'created_at');
        $dir = $request->input('dir', 'desc');

        if ($sort === 'asset') {
            $query->join('assets', 'maintenance_logs.asset_id', '=', 'assets.id')
                  ->select('maintenance_logs.*')
                  ->orderBy('assets.name', $dir);
        } elseif ($sort === 'cost') {
            $query->orderBy('cost', $dir);
        } elseif ($sort === 'status') {
            $query->orderBy('status', $dir);
        } else {
            $query->orderBy($sort, $dir);
        }

        // Pagination
        $perPage = $request->input('per_page', 10);
        if ($perPage === 'all') {
            $logs = $query->paginate($query->count())->withQueryString();
        } else {
            $logs = $query->paginate($perPage)->withQueryString();
        }

        $assets = Asset::orderBy('name')->get(['id', 'name', 'asset_code', 'barcode_code']);

        return Inertia::render('Maintenance/Index', [
            'logs' => $logs,
            'assets' => $assets,
            'filters' => $request->only(['type', 'search', 'sort', 'dir', 'per_page']),
        ]);
    }

    /**
     * Bulk delete maintenance logs.
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:maintenance_logs,id',
        ]);

        MaintenanceLog::whereIn('id', $request->ids)->delete();

        return redirect()->back()->with('success', count($request->ids) . ' log maintenance berhasil dihapus.');
    }

    /**
     * Store a newly created maintenance log.
     * Optionally updates asset status to 'maintenance'.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'type' => 'required|in:repair,replacement,inspection',
            'description' => 'required|string',
            'cost' => 'nullable|numeric|min:0',
            'technician' => 'nullable|string|max:255',
            'completed_at' => 'nullable|date',
        ]);

        $validated['user_id'] = Auth::id();
        
        // Determine log status based on completed_at date
        $isFuture = !empty($validated['completed_at']) && strtotime($validated['completed_at']) > time();
        $validated['status'] = (!empty($validated['completed_at']) && !$isFuture) ? 'selesai' : 'proses';
        
        $log = MaintenanceLog::create($validated);

        // SYNC ASSET STATUS
        $assetStatus = ($validated['status'] === 'selesai') ? 'available' : 'maintenance';
        $asset = Asset::find($validated['asset_id']);
        if ($asset) {
            $asset->update(['status' => $assetStatus]);

            $managingDept = $asset->subcategory->managing_dept ?: 'Pengelola Aset';
            $oldHolder = $asset->current_holder;

            // When in maintenance, we record that the asset was handed from User to Pengelola
            AssetMovement::create([
                'asset_id'         => $asset->id,
                'from_location_id' => $asset->location_id,
                'to_location_id'   => $asset->location_id,
                'user_id'          => Auth::id(),
                'from_holder'      => $oldHolder ?: 'User',
                'to_holder'        => $managingDept,
                'notes'            => 'Barang telah diserahkan pengguna ke pengelola aset. Deskripsi: ' . $log->description,
            ]);
        }

        return redirect()->back()
            ->with('success', 'Log maintenance berhasil ditambahkan.');
    }

    public function update(Request $request, MaintenanceLog $maintenance)
    {
        $validated = $request->validate([
            'type' => 'required|in:repair,replacement,inspection',
            'description' => 'required|string',
            'cost' => 'nullable|numeric|min:0',
            'technician' => 'nullable|string|max:255',
            'completed_at' => 'nullable|date',
        ]);

        // Determine log status based on completed_at date
        $isFuture = !empty($validated['completed_at']) && strtotime($validated['completed_at']) > time();
        $validated['status'] = (!empty($validated['completed_at']) && !$isFuture) ? 'selesai' : 'proses';

        $maintenance->update($validated);

        // SYNC ASSET STATUS
        $assetStatus = ($validated['status'] === 'selesai') ? 'available' : 'maintenance';
        $asset = Asset::find($maintenance->asset_id);
        if ($asset) {
            $asset->update(['status' => $assetStatus]);

            $managingDept = $asset->subcategory->managing_dept ?: 'Pengelola Aset';
            $statusNote = ($maintenance->status === 'selesai') ? 'Selesai' : 'Diperbarui (Dalam Proses)';

            AssetMovement::create([
                'asset_id'         => $asset->id,
                'from_location_id' => $asset->location_id,
                'to_location_id'   => $asset->location_id,
                'user_id'          => Auth::id(),
                'from_holder'      => $maintenance->status === 'selesai' ? $managingDept : $asset->current_holder,
                'to_holder'        => $managingDept,
                'notes'            => '[UPDATE MAINTENANCE] ' . $maintenance->description,
            ]);
        }

        return redirect()->back()
            ->with('success', 'Log maintenance diperbarui.');
    }

    /**
     * Remove the specified maintenance log.
     */
    public function destroy(MaintenanceLog $maintenance)
    {
        $maintenance->delete();

        return redirect()->back()
            ->with('success', 'Log maintenance berhasil dihapus.');
    }

    /**
     * Mark the maintenance log as completed instantly.
     * Also logs the completion event to AssetMovement history.
     */
    public function complete(MaintenanceLog $maintenance)
    {
        $maintenance->update([
            'completed_at' => now()->format('Y-m-d'),
            'status'       => 'selesai',
        ]);

        $asset = Asset::find($maintenance->asset_id);

        if ($asset) {
            $managingDept = $asset->subcategory->managing_dept ?: 'Pengelola Aset';
            
            // Log completion event
            AssetMovement::create([
                'asset_id'         => $asset->id,
                'from_location_id' => $asset->location_id,
                'to_location_id'   => $asset->location_id,
                'user_id'          => Auth::id(),
                'from_holder'      => $managingDept,
                'to_holder'        => $managingDept,
                'notes'            => 'Maintenance selesai: ' . $maintenance->description . '. Aset siap dikembalikan ke pengguna.',
            ]);

            // Update asset: status becomes Available, and current_holder becomes Pengelola Aset
            $asset->update([
                'status' => 'available',
                'current_holder' => $managingDept
            ]);
        }

        return redirect()->back()
            ->with('success', 'Maintenance telah diselesaikan dan aset kini tersedia.');
    }
}
