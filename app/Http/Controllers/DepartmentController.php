<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DepartmentController extends Controller
{
    /**
     * Display a listing of departments.
     */
    public function index()
    {
        $departments = Department::orderBy('name')->get();

        return Inertia::render('Departments/Index', [
            'departments' => $departments,
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
}
