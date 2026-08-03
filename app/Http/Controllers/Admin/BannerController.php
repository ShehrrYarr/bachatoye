<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function index()
    {
        $banners = Banner::orderBy('sort_order')->get();
        return view('admin.banners.index', compact('banners'));
    }

    public function create()
    {
        return view('admin.banners.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'        => 'nullable|string|max:150',
            'subtitle'     => 'nullable|string|max:255',
            'image'        => 'required|image|max:5120',
            'mobile_image' => 'nullable|image|max:3072',
            'link_url'     => 'nullable|url',
            'button_text'  => 'nullable|string|max:50',
            'position'     => 'required|in:hero,promo,sidebar',
            'sort_order'   => 'integer',
            'is_active'    => 'boolean',
            'starts_at'    => 'nullable|date',
            'ends_at'      => 'nullable|date|after_or_equal:starts_at',
        ]);

        $data['image']        = $request->file('image')->store('banners', 'public');
        $data['is_active']    = $request->boolean('is_active', true);

        if ($request->hasFile('mobile_image')) {
            $data['mobile_image'] = $request->file('mobile_image')->store('banners', 'public');
        }

        Banner::create($data);
        $rPrefix = auth()->user()->panelPrefix();
        return redirect()->route("{$rPrefix}.banners.index")->with('success', 'Banner created.');
    }

    public function edit(Banner $banner)
    {
        return view('admin.banners.edit', compact('banner'));
    }

    public function update(Request $request, Banner $banner)
    {
        $data = $request->validate([
            'title'       => 'nullable|string|max:150',
            'subtitle'    => 'nullable|string|max:255',
            'link_url'    => 'nullable|url',
            'button_text' => 'nullable|string|max:50',
            'position'    => 'required|in:hero,promo,sidebar',
            'sort_order'  => 'integer',
            'is_active'   => 'boolean',
            'starts_at'   => 'nullable|date',
            'ends_at'     => 'nullable|date|after_or_equal:starts_at',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('banners', 'public');
        }

        $data['is_active'] = $request->boolean('is_active', true);
        $banner->update($data);
        $rPrefix = auth()->user()->panelPrefix();
        return redirect()->route("{$rPrefix}.banners.index")->with('success', 'Banner updated.');
    }

    public function destroy(Banner $banner)
    {
        $banner->delete();
        $rPrefix = auth()->user()->panelPrefix();
        return redirect()->route("{$rPrefix}.banners.index")->with('success', 'Banner deleted.');
    }

    public function toggle(Banner $banner)
    {
        $banner->update(['is_active' => !$banner->is_active]);
        return back()->with('success', $banner->is_active ? 'Banner activated.' : 'Banner deactivated.');
    }
}
