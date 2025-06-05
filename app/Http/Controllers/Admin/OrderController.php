<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\OrderEmergencyContact;

class OrderController extends Controller
{

    public function store(Request $request)
    {
        $modelMap = [
            'activity' => \App\Models\Activity::class,
            'itinerary' => \App\Models\Itinerary::class,
            'package' => \App\Models\Package::class,
        ];
        $rules = [
            'user_id'              => 'required|exists:users,id',
            'orderable_type'       => ['required', Rule::in(array_keys($modelMap))],
            'orderable_id'         => 'required|integer',
            'travel_date'          => 'required|date',
            'preferred_time'       => 'nullable|date_format:H:i:s',
            'number_of_adults'     => 'required|integer|min:1',
            'number_of_children'   => 'nullable|integer|min:0',
            'status'               => 'nullable|string|in:pending,confirmed,cancelled',
            'special_requirements' => 'nullable|string',

            'payment'              => 'required|array',
            'emergency_contact'    => 'required|array',
        ];

        $validated = $request->validate($rules);

        DB::beginTransaction();

        try {
            // Step 1: Create main order
            $order = Order::create([
                'user_id'              => $validated['user_id'],
                'orderable_type'       => $modelMap[$validated['orderable_type']],
                'orderable_id'         => $validated['orderable_id'],
                'travel_date'          => $validated['travel_date'],
                'preferred_time'       => $validated['preferred_time'] ?? null,
                'number_of_adults'     => $validated['number_of_adults'],
                'number_of_children'   => $validated['number_of_children'] ?? 0,
                'status'               => $validated['status'] ?? 'pending',
                'special_requirements' => $validated['special_requirements'] ?? null,
            ]);

            // Step 2: Create payment
            if (isset($validated['payment'])) {
                $order->payment()->create([
                    'payment_status'    => $validated['payment']['payment_status'] ?? 'pending',
                    'payment_method'    => $validated['payment']['payment_method'] ?? null,
                    'total_amount'      => $validated['payment']['total_amount'] ?? 0,
                    'is_custom_amount'  => $validated['payment']['is_custom_amount'] ?? false,
                    'custom_amount'     => $validated['payment']['custom_amount'] ?? 0,
                ]);
            }

            // Step 3: Create emergency contact
            if (isset($validated['emergency_contact'])) {
                $order->emergencyContact()->create([
                    'contact_name'  => $validated['emergency_contact']['contact_name'] ?? null,
                    'contact_phone' => $validated['emergency_contact']['contact_phone'] ?? null,
                    'relationship'  => $validated['emergency_contact']['relationship'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Order created successfully.',
                'data'    => $order->load(['payment', 'emergencyContact']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create order.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function index(Request $request)
    {
        $perPage = 5;
        $page    = $request->get('page', 1);
        $status  = $request->get('status');

        // Base query for pagination (filtered)
        $query = Order::with(['user', 'orderable', 'payment', 'emergencyContact']);

        if ($status && in_array($status, ['pending', 'confirmed', 'cancelled'])) {
            $query->where('status', $status);
        }

        $filteredCount = $query->count();

        // Use pagination only if filtered results > 5
        if ($filteredCount <= $perPage) {
            $orders = $query->get();
            $isPaginated = false;
        } else {
            $orders = $query->paginate($perPage, ['*'], 'page', $page);
            $isPaginated = true;
        }

        $orderCollection = $isPaginated ? $orders->getCollection() : $orders;

        $formatted = $orderCollection->map(function ($order) {
            return [
                'id'                   => $order->id,
                'order_type'           => strtolower(class_basename($order->orderable_type)),
                'travel_date'          => $order->travel_date,
                'preferred_time'       => $order->preferred_time,
                'number_of_adults'     => $order->number_of_adults,
                'number_of_children'   => $order->number_of_children,
                'status'               => $order->status,
                'special_requirements' => $order->special_requirements,
                'user'                 => $order->user,
                'orderable'            => $order->orderable,
                'payment'              => $order->payment,
                'emergency_contact'    => $order->emergencyContact,
            ];
        });

        // Summary based on **all orders**, NOT filtered
        $allOrders = Order::with('payment')->get();

        $summary = [
            'total_orders'     => $allOrders->count(),
            'pending_orders'   => $allOrders->where('status', 'pending')->count(),
            'confirmed_orders' => $allOrders->where('status', 'confirmed')->count(),
            'cancelled_orders' => $allOrders->where('status', 'cancelled')->count(),
            'total_revenue'    => $allOrders->pluck('payment')->filter()->sum(function ($payment) {
                return ($payment->total_amount ?? 0) + ($payment->custom_amount ?? 0);
            }),
        ];

        // Final Response
        $response = [
            'success' => true,
            'data'    => $formatted,
            'summary' => $summary,
        ];

        if ($isPaginated) {
            $response['current_page'] = $orders->currentPage();
            $response['per_page']     = $orders->perPage();
            $response['total']        = $orders->total();

            if ($formatted->isEmpty()) {
                $response['message'] = $status
                    ? "No more {$status} orders available."
                    : "No more orders available.";
            }
        }

        return response()->json($response);
    }

    public function show($id)
    {
        $order = Order::with(['user', 'orderable', 'payment', 'emergencyContact'])->findOrFail($id);
    
        $formatted = [
            'id'                   => $order->id,
            'type'                 => strtolower(class_basename($order->orderable_type)), // e.g. activity, package
            'travel_date'          => $order->travel_date,
            'preferred_time'       => $order->preferred_time,
            'number_of_adults'     => $order->number_of_adults,
            'number_of_children'   => $order->number_of_children,
            'status'               => $order->status,
            'special_requirements' => $order->special_requirements,
            'user'                 => $order->user,
            'orderable'            => $order->orderable,
            'payment'              => $order->payment,
            'emergency_contact'    => $order->emergencyContact,
            // 'created_at'           => $order->created_at,
        ];
    
        return response()->json([
            'success' => true,
            'data'    => $formatted
        ]);
    }
    
    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $order->delete();
        return response()->json(['message' => 'Order deleted successfully']);
    }
}
