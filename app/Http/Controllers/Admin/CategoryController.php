<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Section;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::with('parent')->withCount('products')->orderBy('sort_order')->paginate(30);
        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        $parents  = Category::whereNull('parent_id')->get();
        $sections = Section::orderBy('sort_order')->get();
        return view('admin.categories.create', compact('parents', 'sections'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:100',
            'parent_id'  => 'nullable|exists:categories,id',
            'section_id' => 'nullable|exists:sections,id',
            'image'      => 'nullable|image|max:2048',
            'sort_order' => 'integer',
            'is_active'  => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        $data['is_active'] = $request->boolean('is_active', true);
        Category::create($data);

        return redirect()->route('admin.categories.index')->with('success', 'Category created.');
    }

    public function edit(Category $category)
    {
        $parents  = Category::whereNull('parent_id')->where('id', '!=', $category->id)->get();
        $sections = Section::orderBy('sort_order')->get();
        return view('admin.categories.edit', compact('category', 'parents', 'sections'));
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:100',
            'parent_id'  => 'nullable|exists:categories,id',
            'section_id' => 'nullable|exists:sections,id',
            'sort_order' => 'integer',
            'is_active'  => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        $data['is_active'] = $request->boolean('is_active', true);
        $category->update($data);

        return redirect()->route('admin.categories.index')->with('success', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return redirect()->route('admin.categories.index')->with('success', 'Category deleted.');
    }
}
