<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CategoryController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:view database', only: ['index']),
            new Middleware('can:create database', only: ['store', 'storeSubcategory']),
            new Middleware('can:edit database', only: ['update', 'updateSubcategory']),
            new Middleware('can:delete database', only: ['destroy', 'destroySubcategory', 'bulkDelete', 'bulkDeleteSub']),
        ];
    }

    /**
     * Display a listing of categories and subcategories.
     */
    public function index(Request $request): Response
    {
        $catPerPage = $request->input('cat_per_page', 10);
        $subPerPage = $request->input('sub_per_page', 10);

        $catLimit = $catPerPage === 'all' ? 9999 : $catPerPage;
        $subLimit = $subPerPage === 'all' ? 9999 : $subPerPage;

        // Query Categories
        $catQuery = Category::withCount('subcategories');
        if ($request->filled('cat_search')) {
            $catQuery->where('name', 'like', '%' . $request->cat_search . '%');
        }
        
        $catSortCol = $request->input('cat_sort', 'name');
        $catSortDir = $request->input('cat_dir', 'asc');
        
        $categories = $catQuery->orderBy($catSortCol, $catSortDir)
            ->paginate($catLimit, ['*'], 'cats')
            ->withQueryString();

        // Query Subcategories
        $subQuery = Subcategory::with('category')->withCount('assets');
        if ($request->filled('sub_search')) {
            $subQuery->where('name', 'like', '%' . $request->sub_search . '%')
                    ->orWhereHas('category', function($q) use ($request) {
                        $q->where('name', 'like', '%' . $request->sub_search . '%');
                    });
        }

        $subSortCol = $request->input('sub_sort', 'name');
        $subSortDir = $request->input('sub_dir', 'asc');

        $subcategories = $subQuery->orderBy($subSortCol, $subSortDir)
            ->paginate($subLimit, ['*'], 'subs')
            ->withQueryString();

        return Inertia::render('Categories/Index', [
            'categories' => fn() => $categories,
            'subcategories' => fn() => $subcategories,
            'filters' => $request->only(['cat_search', 'sub_search', 'cat_per_page', 'sub_per_page', 'cat_sort', 'cat_dir', 'sub_sort', 'sub_dir']),
        ]);
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories',
            'description' => 'nullable|string|max:1000',
        ]);

        Category::create($validated);

        return back()->with('success', 'Kategori berhasil dibuat.');
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'description' => 'nullable|string|max:1000',
        ]);

        $category->update($validated);

        return back()->with('success', 'Kategori berhasil diperbarui.');
    }

    /**
     * Remove the specified category.
     */
    public function destroy(Category $category): RedirectResponse
    {
        if ($category->subcategories()->exists()) {
            return back()->with('error', 'Gagal: Kategori ini masih memiliki subkategori.');
        }

        $category->delete();

        return back()->with('success', 'Kategori berhasil dihapus.');
    }

    /**
     * Store a newly created subcategory.
     */
    public function storeSubcategory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'prefix' => 'nullable|string|max:10',
            'managing_dept' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
        ]);

        Subcategory::create($validated);

        return back()->with('success', 'Subkategori berhasil dibuat.');
    }

    /**
     * Update specified subcategory.
     */
    public function updateSubcategory(Request $request, Subcategory $subcategory): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'prefix' => 'nullable|string|max:10',
            'managing_dept' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
        ]);

        $subcategory->update($validated);

        return back()->with('success', 'Subkategori berhasil diperbarui.');
    }

    /**
     * Remove specified subcategory.
     */
    public function destroySubcategory(Subcategory $subcategory): RedirectResponse
    {
        if ($subcategory->assets()->exists()) {
            return back()->with('error', 'Gagal: Subkategori ini masih memiliki aset.');
        }

        $subcategory->delete();

        return back()->with('success', 'Subkategori berhasil dihapus.');
    }
    /**
     * Remove multiple categories.
     */
    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return back();

        Category::whereIn('id', $ids)->delete();

        return back()->with('success', count($ids) . ' Kategori berhasil dihapus secara massal.');
    }

    /**
     * Remove multiple subcategories.
     */
    public function bulkDeleteSub(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return back();

        Subcategory::whereIn('id', $ids)->delete();

        return back()->with('success', count($ids) . ' Subkategori berhasil dihapus secara massal.');
    }
}
