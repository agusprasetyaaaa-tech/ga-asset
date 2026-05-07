<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class DepartmentController extends Controller implements HasMiddleware
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
     * Display a listing of departments.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $limit = $perPage === 'all' ? 9999 : $perPage;

        // Note: Department model might not have assets relationship in all projects, but assuming it matches Location
        $query = Department::withCount('assets');
        
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
        }

        $sortCol = $request->input('sort', 'name');
        $sortDir = $request->input('dir', 'asc');

        $departments = $query->orderBy($sortCol, $sortDir)
            ->paginate($limit)
            ->withQueryString();

        return Inertia::render('Departments/Index', [
            'departments' => fn() => $departments,
            'filters' => $request->only(['search', 'per_page', 'sort', 'dir']),
        ]);
    }

    /**
     * Store a newly created department.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:departments,code',
            'description' => 'nullable|string',
        ]);

        Department::create($validated);

        return redirect()->route('departments.index')
            ->with('success', 'Departemen berhasil ditambahkan.');
    }

    /**
     * Update the specified department.
     */
    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:departments,code,' . $department->id,
            'description' => 'nullable|string',
        ]);

        $department->update($validated);

        return redirect()->route('departments.index')
            ->with('success', 'Departemen berhasil diperbarui.');
    }

    /**
     * Remove the specified department.
     */
    public function destroy(Department $department)
    {
        // Check if there are assets linked to this department
        if ($department->assets()->count() > 0) {
            return redirect()->back()->with('error', 'Tidak dapat menghapus departemen karena masih memiliki aset terkait.');
        }

        $department->delete();

        return redirect()->route('departments.index')
            ->with('success', 'Departemen berhasil dihapus.');
    }

    /**
     * Remove multiple departments.
     */
    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return back();

        Department::whereIn('id', $ids)->delete();

        return back()->with('success', count($ids) . ' Departemen berhasil dihapus secara massal.');
    }
}
