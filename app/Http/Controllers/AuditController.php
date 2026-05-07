<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\AuditItem;
use App\Models\Asset;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class AuditController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:view activity', only: ['index', 'show', 'exportPdf']),
            new Middleware('can:create activity', only: ['store']),
            new Middleware('can:edit activity', only: ['update', 'complete', 'updateItem']),
            new Middleware('can:delete activity', only: ['destroy', 'bulkDelete']),
        ];
    }
    public function exportPdf(Audit $audit)
    {
        $audit->load(['location', 'user', 'items.asset.subcategory']);
        
        $pdf = Pdf::loadView('exports.audit-pdf', [
            'audit' => $audit,
        ]);

        return $pdf->download('audit_report_' . $audit->audit_no . '.pdf');
    }

    public function index(Request $request)
    {
        $query = Audit::with(['location', 'user']);

        // Search logic
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('audit_no', 'like', "%{$search}%")
                  ->orWhereHas('location', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('user', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $audits = $query->orderByDesc('created_at')
            ->paginate($request->per_page ?? 10)
            ->withQueryString();

        return Inertia::render('Audits/Index', [
            'audits' => $audits,
            'filters' => $request->only(['search', 'status', 'per_page']),
            'locations' => Location::all()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'location_id' => 'required|exists:locations,id',
            'notes' => 'nullable|string',
        ]);

        // Create the audit session
        $audit = Audit::create([
            'audit_no' => Audit::generateAuditNo(),
            'location_id' => $request->location_id,
            'user_id' => Auth::id(),
            'status' => 'draft',
            'notes' => $request->notes,
            'started_at' => now(),
        ]);

        // Get all assets that SHOULD be in this location
        $assets = Asset::where('location_id', $request->location_id)->get();
        
        $audit->update(['total_assets' => $assets->count()]);

        // Prepare audit items (initial status: missing)
        foreach ($assets as $asset) {
            AuditItem::create([
                'audit_id' => $audit->id,
                'asset_id' => $asset->id,
                'status' => 'missing',
            ]);
        }

        return redirect()->route('audits.show', $audit->id)->with('success', 'Sesi Audit dimulai.');
    }

    public function show(Audit $audit)
    {
        $audit->load(['location', 'user', 'items.asset.subcategory']);
        
        return Inertia::render('Audits/Show', [
            'audit' => $audit,
        ]);
    }

    public function scan(Request $request, Audit $audit)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        if ($audit->status === 'completed') {
            return response()->json(['message' => 'Audit sudah selesai.'], 422);
        }

        // Find asset by asset_code or barcode_code
        $asset = Asset::where('asset_code', $request->code)
            ->orWhere('barcode_code', $request->code)
            ->first();

        if (!$asset) {
            return response()->json(['message' => 'Aset tidak ditemukan dalam database.'], 404);
        }

        // Check if this asset was already scanned in this audit
        $auditItem = AuditItem::where('audit_id', $audit->id)
            ->where('asset_id', $asset->id)
            ->first();

        if ($auditItem && $auditItem->status !== 'missing') {
            return response()->json(['message' => 'Aset sudah di-scan sebelumnya.', 'asset' => $asset], 200);
        }

        if ($auditItem) {
            // Asset is supposed to be here
            $auditItem->update([
                'status' => 'found',
                'scanned_at' => now(),
            ]);
        } else {
            // Asset is NOT supposed to be here (mismatch)
            AuditItem::create([
                'audit_id' => $audit->id,
                'asset_id' => $asset->id,
                'status' => 'mismatch',
                'scanned_location' => $asset->location->name ?? 'Unknown',
                'scanned_at' => now(),
            ]);
        }

        // Update audit counts
        $this->updateAuditCounts($audit);

        return response()->json([
            'message' => 'Aset berhasil di-scan.',
            'asset' => $asset->load('subcategory'),
            'audit' => $audit->fresh(['items'])
        ]);
    }

    public function complete(Audit $audit)
    {
        if ($audit->status === 'completed') {
            return redirect()->back();
        }

        $audit->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $this->updateAuditCounts($audit);

        return redirect()->route('audits.index')->with('success', 'Sesi Audit berhasil diselesaikan.');
    }

    public function destroy(Audit $audit)
    {
        $audit->delete();
        return redirect()->route('audits.index')->with('success', 'Riwayat audit dihapus.');
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:audits,id'
        ]);

        Audit::whereIn('id', $request->ids)->delete();

        return redirect()->back()->with('success', 'Sesi audit terpilih berhasil dihapus.');
    }

    private function updateAuditCounts(Audit $audit)
    {
        $counts = AuditItem::where('audit_id', $audit->id)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $audit->update([
            'found_assets' => $counts['found'] ?? 0,
            'missing_assets' => $counts['missing'] ?? 0,
            'mismatch_assets' => $counts['mismatch'] ?? 0,
        ]);
    }
}
