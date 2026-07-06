<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SectionController extends Controller
{
    public function index()
    {
        $sections = Section::withCount('categories')->orderBy('sort_order')->get();
        return view('admin.sections.index', compact('sections'));
    }

    public function create()
    {
        return view('admin.sections.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:100|unique:sections',
            'sort_order'       => 'nullable|integer|min:0',
            'barcode_prefix'   => 'nullable|string|max:10|alpha_num|unique:sections,barcode_prefix',
        ]);

        $data['slug']       = Str::slug($data['name']);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        Section::create($data);

        $rPrefix = auth()->user()->hasRole('admin') ? 'admin' : 'salesman';
        return redirect()->route("{$rPrefix}.sections.index")->with('success', 'Section created.');
    }

    public function edit(Section $section)
    {
        $section->load('categories');
        return view('admin.sections.edit', compact('section'));
    }

    public function update(Request $request, Section $section)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:100|unique:sections,name,' . $section->id,
            'sort_order'       => 'nullable|integer|min:0',
            'barcode_prefix'   => 'nullable|string|max:10|alpha_num|unique:sections,barcode_prefix,' . $section->id,
        ]);

        $data['slug']       = Str::slug($data['name']);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $section->update($data);

        $rPrefix = auth()->user()->hasRole('admin') ? 'admin' : 'salesman';
        return redirect()->route("{$rPrefix}.sections.index")->with('success', 'Section updated.');
    }

    public function destroy(Section $section)
    {
        // Nullify category section_id before deleting
        $section->categories()->update(['section_id' => null]);
        $section->delete();

        $rPrefix = auth()->user()->hasRole('admin') ? 'admin' : 'salesman';
        return redirect()->route("{$rPrefix}.sections.index")->with('success', 'Section deleted.');
    }
}
