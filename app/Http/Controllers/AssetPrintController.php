<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AssetPrintController extends Controller
{
    /**
     * Display the asset selection page for printing barcodes.
     */
    public function index(Request $request)
    {
        $query = Asset::with(['subcategory', 'location']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('asset_code', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('subcategory_id')) {
            $query->where('subcategory_id', $request->subcategory_id);
        }

        if ($request->filled('category_id')) {
            $query->whereHas('subcategory', function($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        $assets = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('Assets/PrintSelect', [
            'assets' => $assets,
            'categories' => \App\Models\Category::all(),
            'subcategories' => \App\Models\Subcategory::all(),
            'departments' => \App\Models\Department::all(),
            'filters' => $request->only(['search', 'subcategory_id', 'category_id', 'department_id']),
        ]);
    }

    /**
     * Show the printable barcode layout for selected assets.
     */
    public function print(Request $request)
    {
        $ids = explode(',', $request->query('ids', ''));
        
        if (empty($ids) || $ids[0] == '') {
            return redirect()->route('assets.print-index')->with('error', 'Pilih aset terlebih dahulu.');
        }

        $assets = Asset::whereIn('id', $ids)->get();

        return Inertia::render('Assets/PrintLayout', [
            'assets' => $assets,
        ]);
    }
}
