<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AssetMovementController extends Controller
{
    /**
     * Store a new movement record.
     * This records asset transfers between locations and/or holders.
     * Also updates the asset's current location and holder.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'to_location_id' => 'nullable|exists:locations,id',
            'to_holder' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $asset = Asset::findOrFail($validated['asset_id']);

        // Create movement record with "from" data from current asset state
        AssetMovement::create([
            'asset_id' => $asset->id,
            'from_location_id' => $asset->location_id,
            'to_location_id' => $validated['to_location_id'],
            'user_id' => Auth::id(),
            'from_holder' => $asset->current_holder,
            'to_holder' => $validated['to_holder'],
            'notes' => $validated['notes'],
        ]);

        // Update asset's current location and holder
        $asset->update([
            'location_id' => $validated['to_location_id'],
            'current_holder' => $validated['to_holder'],
            'status' => 'in_use',
        ]);

        return redirect()->back()
            ->with('success', 'Perpindahan asset berhasil dicatat.');
    }
}
