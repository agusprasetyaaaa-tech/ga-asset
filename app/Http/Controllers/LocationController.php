<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class LocationController extends Controller implements HasMiddleware
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
     * Display a listing of locations.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $limit = $perPage === 'all' ? 9999 : $perPage;

        $query = Location::withCount('assets');
        
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
        }

        $sortCol = $request->input('sort', 'name');
        $sortDir = $request->input('dir', 'asc');

        $locations = $query->orderBy($sortCol, $sortDir)
            ->paginate($limit)
            ->withQueryString();

        return Inertia::render('Locations/Index', [
            'locations' => fn() => $locations,
            'filters' => $request->only(['search', 'per_page', 'sort', 'dir']),
        ]);
    }

    /**
     * Store a newly created location.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        Location::create($validated);

        return redirect()->route('locations.index')
            ->with('success', 'Lokasi berhasil ditambahkan.');
    }

    /**
     * Update the specified location.
     */
    public function update(Request $request, Location $location)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $location->update($validated);

        return redirect()->route('locations.index')
            ->with('success', 'Lokasi berhasil diperbarui.');
    }

    /**
     * Remove the specified location.
     */
    public function destroy(Location $location)
    {
        $location->delete();

        return redirect()->route('locations.index')
            ->with('success', 'Lokasi berhasil dihapus.');
    }

    /**
     * Remove multiple locations.
     */
    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return back();

        Location::whereIn('id', $ids)->delete();

        return back()->with('success', count($ids) . ' Lokasi berhasil dihapus secara massal.');
    }
}
