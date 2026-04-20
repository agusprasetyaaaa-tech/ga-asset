<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicAssetController extends Controller
{
    /**
     * Display the asset information for public view (scanned from QR).
     */
    public function show($code)
    {
        $asset = Asset::where('asset_code', $code)
            ->orWhere('barcode_code', $code)
            ->with([
                'location', 
                'subcategory.category', 
                'department_rel', 
                'vendor_rel',
                'movements.user',
                'maintenanceLogs.user'
            ])
            ->firstOrFail();

        return Inertia::render('Public/AssetDetail', [
            'asset' => $asset,
        ]);
    }
}
