<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LocationController extends Controller
{
    /**
     * Display a listing of locations.
     */
    public function index()
    {
        $locations = Location::withCount('assets')->orderBy('name')->get();

        return Inertia::render('Locations/Index', [
            'locations' => $locations,
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
}
