<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BarcodeScanController extends Controller
{
    /**
     * Display the scanner page.
     */
    public function index()
    {
        return Inertia::render('Scanner/Index');
    }

    /**
     * Lookup asset by scanned barcode code.
     * Returns JSON for AJAX usage from the scanner component.
     */
    public function lookup(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $asset = Asset::with(['location', 'movements.user', 'maintenanceLogs'])
            ->where('barcode_code', $request->code)
            ->orWhere('serial_number', $request->code)
            ->first();

        if (!$asset) {
            return response()->json([
                'found' => false,
                'message' => 'Asset tidak ditemukan untuk kode: ' . $request->code,
            ], 404);
        }

        return response()->json([
            'found' => true,
            'asset' => $asset,
        ]);
    }
}
