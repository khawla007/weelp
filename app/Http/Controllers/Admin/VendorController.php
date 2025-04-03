<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vendor;

class VendorController extends Controller
{
    /**
     * Display a listing of the vendors.
     */
    public function index()
    {
        $vendors = Vendor::paginate(3); // Paginated response
        return response()->json($vendors);
    }

    /**
     * Store a newly created vendors in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified vendors.
     */
    public function show(string $id)
    {
        $vendor = Vendor::with(['routes', 'pricingTiers', 'vehicles', 'drivers'])->find($id);

        if (!$vendor) {
            return response()->json(['message' => 'Vendor not found'], 404);
        }

        return response()->json($vendor);
    }

    /**
     * Update the specified vendors in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }


    /**
     * Create & Update the specified vendors in storage.
     */
    public function save(Request $request, $id = null)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'email'       => 'required|email|unique:vendors,email,' . $id,
            'phone'       => 'nullable|string|max:20',
            'address'     => 'nullable|string',
            'status'      => 'required|in:Active,Inactive,Pending',
        ]);

        $vendor = $id ? Vendor::find($id) : new Vendor();

        if ($id && !$vendor) {
            return response()->json(['message' => 'Vendor not found'], 404);
        }

        $vendor->fill($data);
        $vendor->save();

        return response()->json([
            'message' => $id ? 'Vendor updated successfully' : 'Vendor created successfully',
            'vendor'  => $vendor,
        ]);
    }

    /**
     * Remove the specified vendors from storage.
     */
    public function destroy(string $id)
    {
        $vendor = Vendor::find($id);

        if (!$vendor) {
            return response()->json(['message' => 'Vendor not found'], 404);
        }

        $vendor->delete();

        return response()->json(['message' => 'Vendor deleted successfully']);
    }
}
