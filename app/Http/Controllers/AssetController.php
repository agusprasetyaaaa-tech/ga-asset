<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Location;
use App\Models\AssetMovement;
use App\Models\MaintenanceLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AssetController extends Controller
{
    /**
     * Display a listing of assets with filtering and search.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $limit = $perPage === 'all' ? 9999 : $perPage;
        
        $sort = $request->input('sort', 'created_at');
        $dir = $request->input('dir', 'desc');

        $query = Asset::with(['location', 'subcategory.category'])
            ->withCount('maintenanceLogs');

        // Search by name, serial number, asset code or barcode code
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('asset_code', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%")
                  ->orWhere('model_type', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%");
            });
        }

        // Filters... (Status, Category, Subcategory)
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('category_id')) {
            $query->whereHas('subcategory', fn($q) => $q->where('category_id', $request->category_id));
        }
        if ($request->filled('subcategory_id')) $query->where('subcategory_id', $request->subcategory_id);

        // Advanced Sorting
        if ($sort === 'detail') {
            $query->orderBy('name', $dir);
        } elseif ($sort === 'nilai') {
            $query->orderBy('purchase_price', $dir);
        } else {
            $query->orderBy($sort, $dir);
        }

        $assets = $query->paginate($limit)->withQueryString();
        
        return Inertia::render('Assets/Index', [
            'assets' => $assets,
            'locations' => Location::all(),
            'categories' => \App\Models\Category::all(),
            'subcategories' => \App\Models\Subcategory::all(),
            'departments' => \App\Models\Department::all(),
            'vendors' => \App\Models\Vendor::all(),
            'filters' => $request->only(['search', 'status', 'category_id', 'subcategory_id', 'per_page', 'sort', 'dir']),
        ]);
    }

    /**
     * Store a newly created asset.
     * Automatically generates a unique asset code that is used as barcode.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_code'    => 'nullable|string|max:50|unique:assets,asset_code',
            'name'           => 'required|string|max:255',
            'subcategory_id' => 'required|exists:subcategories,id',
            'brand'          => 'nullable|string|max:255',
            'model_type'     => 'nullable|string|max:255',
            'serial_number'  => 'required|string|max:255|unique:assets,serial_number',
            'specification'  => 'nullable|string',
            'condition'      => 'required|in:baik,cukup_baik,kurang_baik,rusak',
            'status'         => 'required|in:available,in_use,maintenance,damaged',
            'location_id'    => 'nullable|exists:locations,id',
            'department_id'  => 'nullable|exists:departments,id',
            'vendor_id'      => 'nullable|exists:vendors,id',
            'department'     => 'nullable|string|max:255',
            'current_holder' => 'nullable|string|max:255',
            'description'    => 'nullable|string',
            'photo'          => 'nullable|image|max:2048',
            'purchase_date'  => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
            'vendor'         => 'nullable|string|max:255',
            'warranty_date'  => 'nullable|date',
            'usage_period'   => 'nullable|string|max:255',
            'notes'          => 'nullable|string',
            'asset_owner'    => 'nullable|string|max:255',
        ]);

        // Generate asset code if not provided by form
        $deptParam = $request->department_id ?: $request->department;
        $assetCode = $request->input('asset_code') ?: Asset::generateAssetCode($request->subcategory_id, $deptParam, $request->purchase_date);
        $validated['asset_code'] = $assetCode;
        $validated['barcode_code'] = $assetCode;

        // Case 1: We still log the initial input by pengelola, but follow form status
        // (Removed the line forcing status to 'available' to respect form input)

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('assets/photos', 'public');
        }

        $asset = Asset::create($validated);

        // Get managing dept from subcategory for "Pengelola Aset" name
        $managingDept = $asset->subcategory->managing_dept ?: 'Pengelola Aset';

        // Initial movement log: Aset berhasil diinput oleh pengelola aset
        AssetMovement::create([
            'asset_id' => $asset->id,
            'to_location_id' => $asset->location_id,
            'user_id' => Auth::id(),
            'to_holder' => $managingDept,
            'notes' => 'Aset berhasil diinput oleh pengelola aset.',
        ]);

        // If status is immediately 'in_use', we log the handoff to the holder as well
        if ($asset->status === 'in_use' && !empty($asset->current_holder)) {
            AssetMovement::create([
                'asset_id' => $asset->id,
                'from_location_id' => $asset->location_id,
                'to_location_id' => $asset->location_id,
                'user_id' => Auth::id(),
                'from_holder' => $managingDept,
                'to_holder' => $asset->current_holder,
                'notes' => 'Barang langsung diserahkan ke pengguna saat input awal. Departemen: ' . ($asset->department ?: '-'),
            ]);
        }

        return redirect()->route('assets.index')->with('success', 'Asset berhasil ditambahkan.');
    }

    public function show(Asset $asset)
    {
        $asset->load(['location', 'subcategory.category', 'movements.user', 'maintenanceLogs.user']);
        return Inertia::render('Assets/Show', [
            'asset' => $asset,
            'locations' => \App\Models\Location::all(),
            'categories' => \App\Models\Category::all(),
            'subcategories' => \App\Models\Subcategory::all(),
            'departments' => \App\Models\Department::all(),
            'vendors' => \App\Models\Vendor::all(),
        ]);
    }

    public function update(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subcategory_id' => 'required|exists:subcategories,id',
            'brand' => 'nullable|string|max:255',
            'model_type' => 'nullable|string|max:255',
            'serial_number' => 'required|string|max:255|unique:assets,serial_number,' . $asset->id,
            'specification' => 'nullable|string',
            'condition' => 'required|in:baik,cukup_baik,kurang_baik,rusak',
            'status' => 'required|in:available,in_use,maintenance,damaged',
            'location_id' => 'nullable|exists:locations,id',
            'department_id' => 'nullable|exists:departments,id',
            'vendor_id' => 'nullable|exists:vendors,id',
            'department' => 'nullable|string|max:255',
            'current_holder' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
            'vendor' => 'nullable|string|max:255',
            'warranty_date' => 'nullable|date',
            'usage_period' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'asset_owner' => 'nullable|string|max:255',
        ]);

        $oldHolder = $asset->current_holder;
        $oldLocationId = $asset->location_id;

        // Handle photo upload
        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($asset->photo) {
                Storage::disk('public')->delete($asset->photo);
            }
            $validated['photo'] = $request->file('photo')->store('assets/photos', 'public');
        } else {
            // Penting: Hapus key photo dari array agar tidak menimpa foto lama dengan null
            unset($validated['photo']);
        }

        $asset->update($validated);

        // Case 4: Detect holder movement (User A -> User B)
        if ($oldHolder !== $asset->current_holder && !empty($asset->current_holder) && !empty($oldHolder)) {
            AssetMovement::create([
                'asset_id' => $asset->id,
                'from_location_id' => $oldLocationId,
                'to_location_id' => $asset->location_id,
                'user_id' => Auth::id(),
                'from_holder' => $oldHolder,
                'to_holder' => $asset->current_holder,
                'notes' => 'Perpindahan tangan antar pengguna: ' . $oldHolder . ' -> ' . $asset->current_holder,
            ]);
        }

        return back()->with('success', 'Asset berhasil diperbarui.');
    }

    /**
     * Remove the specified asset.
     */
    public function destroy(Asset $asset)
    {
        $asset->delete();

        return redirect()->route('assets.index')
            ->with('success', 'Asset berhasil dihapus.');
    }

    /**
     * Quick status update (e.g., mark as Damaged or Under Maintenance).
     * Also logs the status change to AssetMovement history.
     */
    public function updateStatus(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'status'          => 'required|in:available,in_use,maintenance,damaged',
            'to_holder'       => 'nullable|string|max:255',
            'to_location_id'  => 'nullable|exists:locations,id',
            'notes'           => 'nullable|string',
        ]);

        $oldStatus = $asset->status;
        $newStatus = $validated['status'];

        $statusLabels = [
            'available'   => 'Tersedia',
            'in_use'      => 'Digunakan',
            'maintenance' => 'Dalam Perbaikan',
            'damaged'     => 'Rusak',
        ];

        // Build movement note
        $noteText = $validated['notes']
            ?? 'Status diubah dari "' . ($statusLabels[$oldStatus] ?? $oldStatus) . '" menjadi "' . ($statusLabels[$newStatus] ?? $newStatus) . '".';

        // Update asset
        $updateData = ['status' => $newStatus];
        if (!empty($validated['to_holder'])) {
            $updateData['current_holder'] = $validated['to_holder'];
        }
        if (!empty($validated['to_location_id'])) {
            $updateData['location_id'] = $validated['to_location_id'];
        }
        $asset->update($updateData);

        // Log to movement history
        AssetMovement::create([
            'asset_id'         => $asset->id,
            'from_location_id' => $asset->getOriginal('location_id'),
            'to_location_id'   => $validated['to_location_id'] ?? $asset->location_id,
            'user_id'          => Auth::id(),
            'from_holder'      => $asset->getOriginal('current_holder'),
            'to_holder'        => $validated['to_holder'] ?? $asset->current_holder,
            'notes'            => $noteText,
        ]);

        // If marked as maintenance, auto-create a maintenance log entry
        if ($newStatus === 'maintenance' && $oldStatus !== 'maintenance') {
            MaintenanceLog::create([
                'asset_id'    => $asset->id,
                'user_id'     => Auth::id(),
                'type'        => 'repair',
                'description' => 'Aset ditandai masuk perbaikan (status diubah dari "' . ($statusLabels[$oldStatus] ?? $oldStatus) . '").',
                'status'      => 'proses',
            ]);
        }

        return redirect()->back()
            ->with('success', 'Status aset berhasil diperbarui menjadi "' . ($statusLabels[$newStatus] ?? $newStatus) . '".');
    }

    /**
     * Return an "available" asset back to a user (status becomes in_use).
     * Logs the handoff automatically to movement history.
     */
    public function returnToUser(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'user_name'      => 'required|string|max:255',
            'to_location_id' => 'nullable|exists:locations,id',
            'notes'          => 'nullable|string',
        ]);

        $oldLocationId = $asset->location_id;
        $oldHolder = $asset->current_holder ?: ($asset->subcategory->managing_dept ?: 'Pengelola Aset');
        $managingDept = $asset->subcategory->managing_dept ?: 'Pengelola Aset';

        // Force update status and holder
        $asset->status = 'in_use';
        $asset->current_holder = $validated['user_name'];
        if ($request->filled('to_location_id')) {
            $asset->location_id = $request->to_location_id;
        }
        $asset->save();

        // Case 3: Log Pengelola Aset -> Pengguna Aset
        // Gunakan $oldHolder (GA) dan $validated['user_name'] (Nama Baru) secara eksplisit
        AssetMovement::create([
            'asset_id'         => $asset->id,
            'from_location_id' => $oldLocationId,
            'to_location_id'   => $asset->location_id,
            'user_id'          => Auth::id(),
            'from_holder'      => $oldHolder,
            'to_holder'        => $validated['user_name'],
            'notes'            => 'Barang sudah diserahkan ke pengguna aset. ' . ($validated['notes'] ?? ''),
        ]);

        return redirect()->back()
            ->with('success', 'Aset berhasil diserahkan ke "' . $asset->current_holder . '" dan statusnya menjadi Digunakan.');
    }

    /**
     * Generate standard 2D QR Code based on asset_code.
     */
    public function qrStandard(Asset $asset)
    {
        $code = $asset->asset_code ?: $asset->barcode_code;
        $url = route('public.asset.show', $code);
        
        return response(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
            ->size(200)
            ->margin(1)
            ->generate($url), 200, [
            'Content-Type' => 'image/svg+xml',
        ]);
    }

    /**
     * Generate Stylized (3D-look) QR Code based on asset_code.
     */
    public function qrStylized(Asset $asset)
    {
        $code = $asset->asset_code ?: $asset->barcode_code;
        $url = route('public.asset.show', $code);

        return response(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
            ->size(200)
            ->margin(1)
            ->color(16, 185, 129) // Emerald 500
            ->eye('circle')
            ->style('dot')
            ->generate($url), 200, [
            'Content-Type' => 'image/svg+xml',
        ]);
    }
    /**
     * Remove multiple assets.
     */
    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return back();

        Asset::whereIn('id', $ids)->delete();

        return back()->with('success', count($ids) . ' Aset berhasil dipindahkan ke arsip.');
    }

    /**
     * Display a listing of trashed assets.
     */
    public function trash(Request $request)
    {
        $query = Asset::onlyTrashed()->with(['location', 'subcategory.category']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('asset_code', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%");
            });
        }

        $assets = $query->orderByDesc('deleted_at')->paginate(10)->withQueryString();

        return Inertia::render('Assets/Trash', [
            'assets' => $assets,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Restore the specified asset.
     */
    public function restore($id)
    {
        $asset = Asset::withTrashed()->findOrFail($id);
        $asset->restore();

        return back()->with('success', 'Asset "' . $asset->name . '" berhasil dikembalikan ke inventory.');
    }

    /**
     * Permanently delete the specified asset.
     */
    public function forceDelete($id)
    {
        $asset = Asset::withTrashed()->findOrFail($id);

        // Delete photo permanently
        if ($asset->photo) {
            Storage::disk('public')->delete($asset->photo);
        }

        $asset->forceDelete();

        return back()->with('success', 'Asset "' . $asset->name . '" telah dihapus secara permanen.');
    }

    /**
     * Check if an asset code already exists.
     */
    public function checkCode(Request $request)
    {
        $code = $request->input('code');
        $excludeId = $request->input('exclude_id');

        $exists = Asset::where('asset_code', $code)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->exists();

        return response()->json(['exists' => $exists]);
    }

    /**
     * Restore multiple assets.
     */
    public function bulkRestore(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return back();

        Asset::withTrashed()->whereIn('id', $ids)->restore();

        return back()->with('success', count($ids) . ' Aset berhasil dipulihkan.');
    }

    /**
     * Permanently delete multiple assets.
     */
    public function bulkForceDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return back();

        $assets = Asset::withTrashed()->whereIn('id', $ids)->get();

        foreach ($assets as $asset) {
            if ($asset->photo) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($asset->photo);
            }
            $asset->forceDelete();
        }

        return back()->with('success', count($ids) . ' Aset telah dihapus secara permanen.');
    }
}
