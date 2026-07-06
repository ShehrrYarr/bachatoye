<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index()
    {
        $brands = Brand::withCount('products')->latest()->paginate(20);
        return view('admin.brands.index', compact('brands'));
    }

    public function create()
    {
        return view('admin.brands.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:100|unique:brands',
            'logo'      => 'nullable|image|max:2048',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('brands', 'public');
        }

        $data['is_active'] = $request->boolean('is_active', true);
        Brand::create($data);

        $rPrefix = auth()->user()->hasRole('admin') ? 'admin' : 'salesman';
        return redirect()->route("{$rPrefix}.brands.index")->with('success', 'Brand created.');
    }

    public function edit(Brand $brand)
    {
        return view('admin.brands.edit', compact('brand'));
    }

    public function update(Request $request, Brand $brand)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:100|unique:brands,name,' . $brand->id,
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('brands', 'public');
        }

        $data['is_active'] = $request->boolean('is_active', true);
        $brand->update($data);

        $rPrefix = auth()->user()->hasRole('admin') ? 'admin' : 'salesman';
        return redirect()->route("{$rPrefix}.brands.index")->with('success', 'Brand updated.');
    }

    public function destroy(Brand $brand)
    {
        $brand->delete();
        $rPrefix = auth()->user()->hasRole('admin') ? 'admin' : 'salesman';
        return redirect()->route("{$rPrefix}.brands.index")->with('success', 'Brand deleted.');
    }
}
