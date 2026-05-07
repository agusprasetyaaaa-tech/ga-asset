<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class BarcodeScanController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:view assets'),
        ];
    }
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

        $asset = Asset::with([
                'location',
                'subcategory.category',
                'department_rel',
                'vendor_rel',
                'movements.user',
                'movements.fromLocation',
                'movements.toLocation',
                'maintenanceLogs',
            ])
            ->where('barcode_code', $request->code)
            ->orWhere('serial_number', $request->code)
            ->orWhere('asset_code', $request->code)
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
