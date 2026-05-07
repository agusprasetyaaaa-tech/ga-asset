<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetLoan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class AssetLoanController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:view activity', only: ['index']),
            new Middleware('can:create activity', only: ['store']),
            new Middleware('can:edit activity', only: ['return']),
            new Middleware('can:delete activity', only: ['destroy', 'bulkDelete']),
        ];
    }
    public function index(Request $request)
    {
        $query = AssetLoan::with(['asset.subcategory', 'user']);

        // Search logic
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('borrower_name', 'like', "%{$search}%")
                  ->orWhere('borrower_department', 'like', "%{$search}%")
                  ->orWhereHas('asset', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%")
                        ->orWhere('asset_code', 'like', "%{$search}%");
                  });
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $loans = $query->orderByRaw("status = 'borrowed' DESC")
            ->orderByDesc('loan_date')
            ->paginate($request->per_page ?? 10)
            ->withQueryString();

        return Inertia::render('Loans/Index', [
            'loans' => $loans,
            'filters' => $request->only(['search', 'status', 'per_page'])
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'borrower_name' => 'required|string|max:255',
            'borrower_department' => 'required|string|max:255',
            'expected_return_date' => 'nullable|date|after_or_equal:today',
            'notes' => 'nullable|string',
        ]);

        $asset = Asset::findOrFail($request->asset_id);

        if ($asset->status !== 'available') {
            return redirect()->back()->with('error', 'Aset tidak tersedia untuk dipinjam.');
        }

        // Create loan record
        AssetLoan::create([
            'asset_id' => $asset->id,
            'user_id' => Auth::id(),
            'borrower_name' => $request->borrower_name,
            'borrower_department' => $request->borrower_department,
            'loan_date' => now(),
            'expected_return_date' => $request->expected_return_date,
            'status' => 'borrowed',
            'notes' => $request->notes,
        ]);

        // Update asset status
        $asset->update(['status' => 'borrowed']);

        return redirect()->back()->with('success', 'Aset berhasil dipinjamkan.');
    }

    public function return(Request $request, AssetLoan $loan)
    {
        $request->validate([
            'return_notes' => 'nullable|string',
            'asset_condition' => 'required|in:baik,cukup_baik,kurang_baik,rusak',
        ]);

        if ($loan->status === 'returned') {
            return redirect()->back()->with('error', 'Aset sudah dikembalikan sebelumnya.');
        }

        // Update loan record
        $loan->update([
            'actual_return_date' => now(),
            'status' => 'returned',
            'return_notes' => $request->return_notes,
        ]);

        // Update asset status back to available and update condition
        $loan->asset->update([
            'status' => 'available',
            'condition' => $request->asset_condition
        ]);

        return redirect()->back()->with('success', 'Aset telah dikembalikan.');
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:asset_loans,id'
        ]);

        $loans = AssetLoan::whereIn('id', $request->ids)->get();

        foreach ($loans as $loan) {
            if ($loan->status === 'borrowed') {
                $loan->asset->update(['status' => 'available']);
            }
            $loan->delete();
        }

        return redirect()->back()->with('success', 'Data peminjaman terpilih berhasil dihapus.');
    }
}
