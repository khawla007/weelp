<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Pagination\Paginator;
use App\Models\Addon;
use Illuminate\Http\Request;

class AddonController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Force paginator to use ?page=X
        Paginator::currentPageResolver(function () use ($request) {
            return (int) $request->input('page', 1);
        });

        $query = Addon::orderBy('id', 'desc');

        $addons = $query->paginate(5);

        // Custom clean response
        return response()->json([
            'data'         => $addons->items(),
            'current_page' => $addons->currentPage(),
            'per_page'     => $addons->perPage(),
            'total'        => $addons->total(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'type'              => 'nullable|string|max:100',
            'description'       => 'nullable|string',
            'price'             => 'required|numeric|min:0',
            'sale_price'        => 'nullable|numeric|min:0',
            'price_calculation' => 'required|string',
            'active_status'     => 'boolean',
        ]);

        $addon = Addon::create($validated);

        return response()->json(['success' => true, 'data' => $addon], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $addon = Addon::findOrFail($id);
    
        return response()->json([
            'success' => true,
            'data'    => $addon
        ]);
    }    

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name'              => 'sometimes|string|max:255',
            'type'              => 'nullable|string|max:100',
            'description'       => 'nullable|string',
            'price'             => 'sometimes|numeric|min:0',
            'sale_price'        => 'nullable|numeric|min:0',
            'price_calculation' => 'sometimes|in:per_person,per_activity,per_package,per_transfer',
            'active_status'     => 'boolean',
        ]);
    
        $addon = Addon::findOrFail($id);
    
        // सिर्फ वही fields update होंगे जो request में आए
        $addon->update($validated);
    
        return response()->json([
            'success' => true,
            'data'    => $addon
        ]);
    }
    

    /**
     * Destroy the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $addon = Addon::findOrFail($id);
    
        $addon->delete();
    
        return response()->json([
            'success' => true,
            'message' => 'Addon deleted successfully.'
        ]);
    }
    
    /**
     * Bulk Destroy the specified resource from storage.
    */
    public function bulkDelete(Request $request)   // 👈 यही नाम
    {
        $validated = $request->validate([
            'addon_ids'   => 'required|array',
            'addon_ids.*' => 'integer',
        ]);
    
        // कितने IDs database में मौजूद हैं?
        $foundCount = Addon::whereIn('id', $validated['addon_ids'])->count();
    
        if ($foundCount === 0) {
            return response()->json([
                'success' => false,
                'message' => 'ID(s) not exist',
            ], 404);
        }
    
        // अब delete करो
        $deletedCount = Addon::whereIn('id', $validated['addon_ids'])->delete();
    
        return response()->json([
            'success' => true,
            'message' => "{$deletedCount} addon(s) deleted successfully",
            // 'deleted_ids' => $validated['addon_ids'],
        ]);
    }
    

}
