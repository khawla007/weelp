<?php

namespace App\Http\Controllers;

use App\Models\ActivityAttribute;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ActivityAttributeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(ActivityAttribute::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:activity_attributes,name',
            'type' => 'required|in:single_select,multi_select,text,number,yes_no',
            'description' => 'nullable|string',
            'values' => 'nullable|array',
            'default_value' => 'nullable|string',
        ]);

        $slug = Str::slug($request->name, '-');
        $taxonomy = 'act_' . $slug;

        $attribute = ActivityAttribute::create([
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
        return response()->json(ActivityAttribute::findOrFail($id));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $attribute = ActivityAttribute::findOrFail($id);

        $request->validate([
            'name' => 'required|unique:activity_attributes,name,' . $id,
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
        ActivityAttribute::findOrFail($id)->delete();
        return response()->json(['message' => 'Attribute deleted successfully']);
    }
}
