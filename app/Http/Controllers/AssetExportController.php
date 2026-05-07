<?php

namespace App\Http\Controllers;

use App\Exports\AssetsExport;
use App\Exports\AssetImportTemplate;
use App\Imports\AssetsImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Asset;

class AssetExportController extends Controller
{
    /**
     * Export assets to Excel with current filters.
     */
    public function excel(Request $request)
    {
        $filters = $request->only(['search', 'status', 'category_id', 'subcategory_id']);
        $filename = 'assets_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new AssetsExport($filters), $filename);
    }

    /**
     * Export assets to PDF with current filters.
     */
    public function pdf(Request $request)
    {
        $query = Asset::with(['subcategory.category', 'location', 'department_rel', 'vendor_rel']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('asset_code', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('category_id')) {
            $query->whereHas('subcategory', fn($q) => $q->where('category_id', $request->category_id));
        }
        if ($request->filled('subcategory_id')) $query->where('subcategory_id', $request->subcategory_id);

        $assets = $query->orderBy('created_at', 'desc')->get();

        $pdf = Pdf::loadView('exports.assets-pdf', [
            'assets' => $assets,
            'generatedAt' => now()->format('d/m/Y H:i'),
            'totalAssets' => $assets->count(),
            'totalValue' => $assets->sum('purchase_price'),
        ]);

        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('assets_' . now()->format('Y-m-d_His') . '.pdf');
    }

    /**
     * Import assets from Excel file.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ], [
            'file.required' => 'File wajib dipilih.',
            'file.mimes' => 'Format file harus .xlsx, .xls, atau .csv',
            'file.max' => 'Ukuran file maksimal 10MB.',
        ]);

        try {
            $import = new AssetsImport;
            Excel::import($import, $request->file('file'));

            $successCount = $import->getRowCount();
            $errors = $import->getErrors();

            $message = "Berhasil mengimpor {$successCount} aset.";
            if (count($errors) > 0) {
                $message .= " " . count($errors) . " baris gagal.";
            }

            return redirect()->back()->with([
                'success' => $message,
                'importErrors' => $errors,
            ]);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errors = [];
            foreach ($failures as $failure) {
                $errors[] = "Baris {$failure->row()}: " . implode(', ', $failure->errors());
            }
            return redirect()->back()->with('importErrors', $errors)->withErrors(['file' => 'Beberapa data tidak valid.']);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['file' => 'Gagal mengimpor: ' . $e->getMessage()]);
        }
    }

    /**
     * Download import template.
     */
    public function template()
    {
        return Excel::download(new AssetImportTemplate, 'template_import_aset.xlsx');
    }
}
