<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SerialAttributeDefinition;
use Illuminate\Http\Request;

class SerialAttributeController extends Controller
{
    public function index()
    {
        $definitions = SerialAttributeDefinition::orderBy('sort_order')->orderBy('name')->get();
        return view('admin.serials.attributes', compact('definitions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:100|unique:serial_attribute_definitions,name',
            'options' => 'required|string', // comma-separated
        ]);

        $options = array_values(array_filter(
            array_map('trim', explode(',', $data['options'])),
            fn($v) => $v !== ''
        ));

        if (empty($options)) {
            return back()->withErrors(['options' => 'Please provide at least one option.'])->withInput();
        }

        $maxOrder = SerialAttributeDefinition::max('sort_order') ?? 0;

        SerialAttributeDefinition::create([
            'name'       => $data['name'],
            'options'    => $options,
            'sort_order' => $maxOrder + 1,
        ]);

        return back()->with('success', "Attribute \"{$data['name']}\" created.");
    }

    public function update(Request $request, SerialAttributeDefinition $serialAttribute)
    {
        $data = $request->validate([
            'name'      => "required|string|max:100|unique:serial_attribute_definitions,name,{$serialAttribute->id}",
            'options'   => 'required|string',
            'is_active' => 'nullable|boolean',
        ]);

        $options = array_values(array_filter(
            array_map('trim', explode(',', $data['options'])),
            fn($v) => $v !== ''
        ));

        if (empty($options)) {
            return back()->withErrors(['options' => 'Please provide at least one option.'])->withInput();
        }

        $serialAttribute->update([
            'name'      => $data['name'],
            'options'   => $options,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Attribute updated.');
    }

    public function destroy(SerialAttributeDefinition $serialAttribute)
    {
        $serialAttribute->delete();
        return back()->with('success', 'Attribute deleted.');
    }
}
