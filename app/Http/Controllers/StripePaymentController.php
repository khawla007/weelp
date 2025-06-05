<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\Order;
use App\Models\Payment;
use App\Models\EmergencyContact;


class StripePaymentController extends Controller
{
    // public function createPaymentIntent(Request $request)
    // {
    //     $request->validate([
    //         'amount' => 'required|numeric',
    //         'currency' => 'required|string',
    //         'description' => 'nullable|string',
    //         'customer_email' => 'nullable|email',
    //     ]);

    //     $response = Http::withBasicAuth(env('STRIPE_SECRET_KEY'), '')
    //         ->asForm()
    //         ->post('https://api.stripe.com/v1/payment_intents', [
    //             'amount' => $request->amount,
    //             'currency' => $request->currency,
    //             'description' => $request->description ?? '',
    //             'receipt_email' => $request->customer_email ?? null,
    //             'payment_method_types[]' => 'card',
    //         ]);

    //     if ($response->failed()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Payment Intent creation failed',
    //             'error' => $response->json(),
    //         ], 500);
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'data' => $response->json(),
    //     ]);
    // }

    public function bookAndCreatePaymentIntent(Request $request)
    {
        $request->validate([
            'order_type'           => 'required|in:activity,itinerary,package',
            'orderable_id'         => 'required|integer',
            'travel_date'          => 'required|date',
            'preferred_time'       => 'nullable|date_format:H:i:s',
            'number_of_adults'     => 'nullable|integer',
            'number_of_children'   => 'nullable|integer',
            'special_requirements' => 'nullable|string',
            'user_id'              => 'required|exists:users,id',
            'customer_email'       => 'required|email',
            'amount'               => 'required|numeric',
            'currency'             => 'required|string',
            'emergency_contact'    => 'nullable|array',
        ]);
    
        DB::beginTransaction();
    
        try {
            // Fetch user
            $user = User::findOrFail($request->user_id);
    
            // Determine the orderable model
            $orderableClass = 'App\\Models\\' . ucfirst($request->order_type);
            $orderable = $orderableClass::findOrFail($request->orderable_id);
    
            // Create Order
            $order = Order::create([
                'user_id'              => $request->user_id,
                'order_type'           => $request->order_type,
                'orderable_type'       => $orderableClass,
                'orderable_id'         => $request->orderable_id,
                'travel_date'          => $request->travel_date,
                'preferred_time'       => $request->preferred_time,
                'number_of_adults'     => $request->number_of_adults,
                'number_of_children'   => $request->number_of_children,
                'status'               => 'pending',
                'special_requirements' => $request->special_requirements,
            ]);
    
            // Emergency Contact (optional)
            if ($request->has('emergency_contact')) {
                EmergencyContact::create([
                    'order_id'     => $order->id,
                    'name'         => $request->emergency_contact['name'] ?? '',
                    'phone'        => $request->emergency_contact['phone'] ?? '',
                    'relationship' => $request->emergency_contact['relationship'] ?? '',
                ]);
            }
    
            // Create Stripe Payment Intent
            $stripeResponse = Http::withBasicAuth(env('STRIPE_SECRET_KEY'), '')
                ->asForm()
                ->post('https://api.stripe.com/v1/payment_intents', [
                    'amount' => $request->amount,
                    'currency' => $request->currency,
                    'description' => 'Booking for ' . ucfirst($request->order_type) . ' #' . $request->orderable_id,
                    'receipt_email' => $request->customer_email,
                    'payment_method_types[]' => 'card',
                    'metadata' => [
                        'user_id'    => $user->id,
                        'user_name'  => $user->name,
                        'user_email' => $user->email,
                        'item_name'  => $orderable->name ?? 'Item',
                        'order_id'   => $order->id,
                    ],
                ]);
    
            if ($stripeResponse->failed()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Stripe payment intent creation failed',
                    'error'   => $stripeResponse->json(),
                ], 500);
            }
    
            // Save Payment
            Payment::create([
                'order_id'        => $order->id,
                'payment_status'  => 'pending',
                'payment_method'  => 'card',
                'total_amount'    => $request->amount,
                'is_custom_amount'=> false,
                'custom_amount'   => null,
            ]);
    
            DB::commit();
    
            return response()->json([
                'success' => true,
                'message' => 'Order and Payment Intent created successfully.',
                'data' => [
                    'order_id'        => $order->id,
                    'payment_intent'  => $stripeResponse->json(),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }    
}
