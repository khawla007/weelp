<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AttributeController extends Controller
{
    /**
     * Display a listing of the Attribute.
     */
    public function index()
    {
        // return response()->json(Attribute::all());
        $attributes = Attribute::all();
        return response()->json([
            'success' => true,
            'data' => $attributes
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:attributes,name',
            'type' => 'required|in:single_select,multi_select,text,number,yes_no',
            'description' => 'nullable|string',
            'values' => 'nullable|array',
            'default_value' => 'nullable|string',
        ]);

        $slug = Str::slug($request->name, '-');
        $taxonomy = 'act_' . $slug;

        $attribute = Attribute::create([
            'name' => $request->name,
            'slug' => $slug,
            'type' => $request->type,
            'description' => $request->description,
            'values' => $request->type === 'single_select' || $request->type === 'multi_select' ? json_encode($request->values) : null,
            'default_value' => in_array($request->type, ['single_select', 'multi_select', 'text', 'number']) ? $request->default_value : null,
            'taxonomy' => $taxonomy,
        ]);

        return response()->json($attribute, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return response()->json(Attribute::findOrFail($id));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $attribute = Attribute::findOrFail($id);

        $request->validate([
            'name' => 'required|unique:attributes,name,' . $id,
            'type' => 'required|in:single_select,multi_select,text,number,yes_no',
            'description' => 'nullable|string',
            'values' => 'nullable|array',
            'default_value' => 'nullable|string',
        ]);

        $slug = Str::slug($request->name, '-');
        $taxonomy = 'act_' . $slug;

        $attribute->update([
            'name' => $request->name,
            'slug' => $slug,
            'type' => $request->type,
            'description' => $request->description,
            'values' => $request->type === 'single_select' || $request->type === 'multi_select' ? json_encode($request->values) : null,
            'default_value' => in_array($request->type, ['single_select', 'multi_select', 'text', 'number']) ? $request->default_value : null,
            'taxonomy' => $taxonomy,
        ]);

        return response()->json($attribute);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Attribute::findOrFail($id)->delete();
        return response()->json(['message' => 'Attribute deleted successfully']);
    }
}
