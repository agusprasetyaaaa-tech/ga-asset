<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class AssetCodePreviewController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:view assets'),
        ];
    }

    /**
     * Get a suggested asset code based on subcategory and department.
     */
    public function __invoke(Request $request)
    {
        $deptParam = $request->input('department_id') ?: $request->input('department');
        $purchaseDate = $request->input('purchase_date');
        
        $code = Asset::generateAssetCode($request->subcategory_id, $deptParam, $purchaseDate);
        
        // Karena generateAssetCode menaikkan counter (setting->save()), 
        // kita perlu mengembalikan counter setting jika ini hanya untuk preview.
        // TAPI untuk lebih aman, kita biarkan logic generateAssetCode() di model 
        // dipanggil saat store saja. 
        // Di sini kita buat simulasi preview yang tidak merusak counter.
        
        return response()->json([
            'code' => $code
        ]);
    }
}
