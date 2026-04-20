<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VendorController extends Controller
{
    /**
     * Display a listing of vendors.
     */
    public function index()
    {
        $vendors = Vendor::orderBy('name')->get();

        return Inertia::render('Vendors/Index', [
            'vendors' => $vendors,
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
}
