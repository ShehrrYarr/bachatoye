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
        $search = request('q');
        $categories = Category::whereNull('parent_id')
            ->when($search, fn($query) => $query->where(fn($sub) =>
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhereHas('children', fn($child) => $child->where('name', 'like', "%{$search}%"))
            ))
            ->with(['children' => fn($q) => $q->withCount('subcategoryProducts')])
            ->withCount(['products', 'children'])
            ->orderBy('sort_order')
            ->get();
        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        $parents    = Category::whereNull('parent_id')->orderBy('name')->get();
        $sections   = Section::orderBy('sort_order')->get();
        $selectedParent = request('parent_id');
        return view('admin.categories.create', compact('parents', 'sections', 'selectedParent'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:100',
            'parent_id'     => 'nullable|exists:categories,id',
            'section_id'    => 'nullable|exists:sections,id',
            'image'         => 'nullable|image|max:2048',
            'sort_order'    => 'integer',
            'is_active'     => 'boolean',
            'free_delivery' => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        $data['is_active']     = $request->boolean('is_active', true);
        $data['free_delivery'] = $request->boolean('free_delivery');
        Category::create($data);

        $rPrefix = auth()->user()->panelPrefix();
        return redirect()->route("{$rPrefix}.categories.index")->with('success', 'Category created.');
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
            'name'          => 'required|string|max:100',
            'parent_id'     => 'nullable|exists:categories,id',
            'section_id'    => 'nullable|exists:sections,id',
            'sort_order'    => 'integer',
            'is_active'     => 'boolean',
            'free_delivery' => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        $data['is_active']     = $request->boolean('is_active', true);
        $data['free_delivery'] = $request->boolean('free_delivery');
        $category->update($data);

        $rPrefix = auth()->user()->panelPrefix();
        return redirect()->route("{$rPrefix}.categories.index")->with('success', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        $category->delete();
        $rPrefix = auth()->user()->panelPrefix();
        return redirect()->route("{$rPrefix}.categories.index")->with('success', 'Category deleted.');
    }
}
