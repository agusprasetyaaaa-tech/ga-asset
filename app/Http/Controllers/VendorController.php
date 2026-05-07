<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class VendorController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:view database', only: ['index']),
            new Middleware('can:create database', only: ['store']),
            new Middleware('can:edit database', only: ['update']),
            new Middleware('can:delete database', only: ['destroy', 'bulkDelete']),
        ];
    }
    /**
     * Display a listing of vendors.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $limit = $perPage === 'all' ? 9999 : $perPage;

        $query = Vendor::withCount('assets');
        
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('contact_person', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('address', 'like', '%' . $request->search . '%');
        }

        $sortCol = $request->input('sort', 'name');
        $sortDir = $request->input('dir', 'asc');

        $vendors = $query->orderBy($sortCol, $sortDir)
            ->paginate($limit)
            ->withQueryString();

        return Inertia::render('Vendors/Index', [
            'vendors' => fn() => $vendors,
            'filters' => $request->only(['search', 'per_page', 'sort', 'dir']),
        ]);
    }

    /**
     * Store a newly created vendor.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        Vendor::create($validated);

        return redirect()->route('vendors.index')
            ->with('success', 'Vendor berhasil ditambahkan.');
    }

    /**
     * Update the specified vendor.
     */
    public function update(Request $request, Vendor $vendor)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $vendor->update($validated);

        return redirect()->route('vendors.index')
            ->with('success', 'Vendor berhasil diperbarui.');
    }

    /**
     * Remove the specified vendor.
     */
    public function destroy(Vendor $vendor)
    {
        // Check if there are assets linked to this vendor
        if ($vendor->assets()->count() > 0) {
            return redirect()->back()->with('error', 'Tidak dapat menghapus vendor karena masih memiliki aset terkait.');
        }

        $vendor->delete();

        return redirect()->route('vendors.index')
            ->with('success', 'Vendor berhasil dihapus.');
    }

    /**
     * Remove multiple vendors.
     */
    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return back();

        Vendor::whereIn('id', $ids)->delete();

        return back()->with('success', count($ids) . ' Vendor berhasil dihapus secara massal.');
    }
}
