<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    // List categories with optional search and pagination.
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $baseQuery = Category::query();
        $categories = (clone $baseQuery)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        if ($search !== '') {
            $categories->getCollection()->each(function ($category) use ($baseQuery) {
                $category->setAttribute(
                    'list_serial',
                    (clone $baseQuery)->where('id', '<', $category->id)->count() + 1
                );
            });
        }

        return view('categories.index', compact('categories', 'search'));
    }

    // Show the category creation form.
    public function create()
    {
        return view('categories.create');
    }

    // Validate and save a new category.
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('categories', 'name'),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', Rule::in(['0', '1'])],
        ]);

        Category::create($validated);

        return redirect()
            ->route('categories.index')
            ->with('success', 'Category added successfully.');
    }

    // Show the category edit form.
    public function edit(Category $category)
    {
        return view('categories.edit', compact('category'));
    }

    // Validate and save changes to a category.
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('categories', 'name')->ignore($category->id),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', Rule::in(['0', '1'])],
        ]);

        $category->update($validated);

        return redirect()
            ->route('categories.index')
            ->with('success', 'Category updated successfully.');
    }

    // Delete a category when no products depend on it.
    public function destroy(Category $category)
    {
        if ($category->products()->exists()) {
            return redirect()
                ->route('categories.index')
                ->with(
                    'error',
                    'Cannot delete this category because products are assigned to it.'
                );
        }

        $category->delete();

        return redirect()
            ->route('categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}
