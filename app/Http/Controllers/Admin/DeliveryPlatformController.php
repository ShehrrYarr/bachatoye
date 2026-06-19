<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryPlatform;
use Illuminate\Http\Request;

class DeliveryPlatformController extends Controller
{
    public function index()
    {
        $platforms = DeliveryPlatform::latest()->get();
        return view('admin.delivery-platforms.index', compact('platforms'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:100',
            'contact' => 'nullable|string|max:100',
            'notes'   => 'nullable|string|max:500',
        ]);

        DeliveryPlatform::create($data);
        return back()->with('success', 'Delivery platform added.');
    }

    public function update(Request $request, DeliveryPlatform $deliveryPlatform)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:100',
            'contact' => 'nullable|string|max:100',
            'notes'   => 'nullable|string|max:500',
            'active'  => 'nullable|boolean',
        ]);

        $deliveryPlatform->update([
            'name'    => $data['name'],
            'contact' => $data['contact'] ?? null,
            'notes'   => $data['notes'] ?? null,
            'active'  => isset($data['active']) ? (bool) $data['active'] : true,
        ]);

        return back()->with('success', 'Platform updated.');
    }

    public function destroy(DeliveryPlatform $deliveryPlatform)
    {
        if ($deliveryPlatform->orders()->exists() || $deliveryPlatform->payouts()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete a platform that has orders or payouts linked to it.']);
        }

        $deliveryPlatform->delete();
        return back()->with('success', 'Platform deleted.');
    }
}
