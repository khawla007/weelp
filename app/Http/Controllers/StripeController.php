<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\OrderEmergencyContact;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;

class StripeController extends Controller
{

    public function createCheckoutSession(Request $request)
    {
        // ✅ Validate input
        $data = $request->validate([
            'order_type' => 'required|string',
            'orderable_id' => 'required|integer',
            'travel_date' => 'required|date',
            'preferred_time' => 'required',
            'number_of_adults' => 'required|integer',
            'number_of_children' => 'required|integer',
            'special_requirements' => 'nullable|string',
            'user_id' => 'required|integer',
            'customer_email' => 'required|email',
            'amount' => 'required|numeric|min:0',
            'is_custom_amount' => 'required|boolean',
            'custom_amount' => 'nullable|numeric|min:0|required_if:is_custom_amount,true',
            'currency' => 'required|string',
            'emergency_contact.name' => 'required|string',
            'emergency_contact.phone' => 'required|string',
            'emergency_contact.relationship' => 'required|string',
        ]);

        // ✅ Determine the orderable model
        $orderableClass = 'App\\Models\\' . ucfirst($data['order_type']);
        $orderable = $orderableClass::findOrFail($data['orderable_id']);

        // ✅ Calculate total amount
        $totalAmount = $data['is_custom_amount'] ? $data['custom_amount'] : $data['amount'];

        // ✅ Check if existing pending order with same data already exists
        $existingOrder = Order::where('user_id', $data['user_id'])
            ->where('orderable_type', $orderableClass)
            ->where('orderable_id', $data['orderable_id'])
            ->where('travel_date', $data['travel_date'])
            ->where('preferred_time', $data['preferred_time'])
            ->whereHas('payment', function ($q) use ($totalAmount) {
                $q->where('payment_status', 'pending')
                ->where('total_amount', $totalAmount); // ensure same amount too
            })
            ->latest()
            ->first();

        if ($existingOrder) {
            return response()->json([
                'id' => $existingOrder->payment->stripe_session_id,
                'url' => 'https://checkout.stripe.com/pay/' . $existingOrder->payment->stripe_session_id,
            ]);
        }

        // ✅ Create Order
        $order = Order::create([
            'user_id' => $data['user_id'],
            'orderable_type' => $orderableClass,
            'orderable_id' => $data['orderable_id'],
            'travel_date' => $data['travel_date'],
            'preferred_time' => $data['preferred_time'],
            'number_of_adults' => $data['number_of_adults'],
            'number_of_children' => $data['number_of_children'],
            'special_requirements' => $data['special_requirements'],
        ]);

        // ✅ Save emergency contact
        $order->emergencyContact()->create([
            'contact_name' => $data['emergency_contact']['name'],
            'contact_phone' => $data['emergency_contact']['phone'],
            'relationship' => $data['emergency_contact']['relationship'],
        ]);

        // ✅ Setup Stripe
        Stripe::setApiKey(env('STRIPE_SECRET'));

        $checkoutSession = StripeSession::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => $data['currency'],
                    'product_data' => [
                        'name' => 'Trip Booking for ' . $data['travel_date'],
                    ],
                    'unit_amount' => $totalAmount * 100, // in cents
                ],
                'quantity' => 1,
            ]],
            'mode'           => 'payment',
            'customer_email' => $data['customer_email'],
            'success_url'    => env('FRONTEND_URL') . '/checkout/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'     => env('FRONTEND_URL') . '/checkout',
        ]);

        // ✅ Save payment record
        $order->payment()->create([
            'payment_status'    => 'pending',
            'payment_method'    => 'credit_card',
            'amount'            => $data['amount'],
            'is_custom_amount'  => $data['is_custom_amount'],
            'custom_amount'     => $data['custom_amount'],
            'total_amount'      => $totalAmount,
            'currency'          => $data['currency'],
            'stripe_session_id' => $checkoutSession->id,
        ]);

        return response()->json([
            'id' => $checkoutSession->id,
            'url' => $checkoutSession->url,
        ]);
    }


    public function confirmPayment(Request $request)
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));

        $sessionId = $request->input('session_id');

        try {
            $session = StripeSession::retrieve($sessionId);

            if ($session->payment_status !== 'paid') {
                return response()->json(['error' => 'Payment not completed'], 400);
            }

            // Find the order_payment record using session_id
            $payment = OrderPayment::where('stripe_session_id', $sessionId)->first();

            if (!$payment) {
                return response()->json(['error' => 'Payment record not found'], 404);
            }

            // Update payment status
            $payment->update([
                'payment_status' => 'paid',
            ]);

            // Fetch associated order
            // $order = Order::find($payment->order_id);
            $order = Order::with(['emergencyContact', 'payment'])->find($payment->order_id);

            if ($payment->fresh()->payment_status === 'paid' && $order) {
                $order->update([
                    'status' => 'success',
                ]);
            }
            
            $user = User::find($order->user_id);

            // Get item name and price based on orderable_type
            $orderableType = class_basename($order->orderable_type);
            $orderableId = $order->orderable_id;

            $itemName = null;
            $itemPrice = null;

            switch ($orderableType) {
                case 'Activity':
                    $activity = \App\Models\Activity::with('pricing')->where('id', $orderableId)->first();
                    $itemName = $activity?->name;
                    $itemPrice = $activity?->pricing?->regular_price;
                    break;
            
                case 'Package':
                    $package = \App\Models\Package::with('basePricing.variations')->where('id', $orderableId)->first();
                    $itemName = $package?->name;
                    $itemPrice = $package?->basePricing?->priceVariations?->first()?->regular_price;
                    break;
            
                case 'Itinerary':
                    $itinerary = \App\Models\Itinerary::with('basePricing.variations')->where('id', $orderableId)->first();
                    $itemName = $itinerary?->name;
                    $itemPrice = $itinerary?->basePricing?->priceVariations?->first()?->regular_price;
                    break;
            }


            $itemDetail = [
                'item_name' => $itemName,
                'item_price' => $itemPrice,
                'total_paid' => $payment->total_amount,
            ];
    
            $userDetail = [
                'name' => $user?->name,
                'email' => $user?->email,
            ];
    
            $orderDetail = [
                'user_detail' => $userDetail,
                'item_detail' => $itemDetail,
                'order' => $order->fresh(['payment', 'emergencyContact']),
            ];

            return response()->json([
                'success' => true,
                'data' => $orderDetail
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong: ' . $e->getMessage()], 500);
        }
    }

    // public function createCheckoutSession(Request $request)
    // {
    //     // ✅ Validate input (updated types)
    //     $data = $request->validate([
    //         'order_type' => 'required|string',
    //         'orderable_id' => 'required|integer',
    //         'travel_date' => 'required|date',
    //         'preferred_time' => 'required',
    //         'number_of_adults' => 'required|integer',
    //         'number_of_children' => 'required|integer',
    //         'special_requirements' => 'nullable|string',
    //         'user_id' => 'required|integer',
    //         'customer_email' => 'required|email',
    //         'amount' => 'required|numeric|min:0',
    //         'is_custom_amount' => 'required|boolean',
    //         'custom_amount' => 'nullable|numeric|min:0|required_if:is_custom_amount,true',
    //         'currency' => 'required|string',
    //         'emergency_contact.name' => 'required|string',
    //         'emergency_contact.phone' => 'required|string',
    //         'emergency_contact.relationship' => 'required|string',
    //     ]);
    
    //     // ✅ Determine the orderable model
    //     $orderableClass = 'App\\Models\\' . ucfirst($data['order_type']);
    //     $orderable = $orderableClass::findOrFail($data['orderable_id']);
    
    //     // ✅ Create Order
    //     $order = Order::create([
    //         'user_id' => $data['user_id'],
    //         'orderable_type' => $orderableClass,
    //         'orderable_id' => $data['orderable_id'],
    //         'travel_date' => $data['travel_date'],
    //         'preferred_time' => $data['preferred_time'],
    //         'number_of_adults' => $data['number_of_adults'],
    //         'number_of_children' => $data['number_of_children'],
    //         'special_requirements' => $data['special_requirements'],
    //     ]);
    
    //     // ✅ Save emergency contact
    //     $order->emergencyContact()->create([
    //         'contact_name' => $data['emergency_contact']['name'],
    //         'contact_phone' => $data['emergency_contact']['phone'],
    //         'relationship' => $data['emergency_contact']['relationship'],
    //     ]);
    
    //     // ✅ Calculate total amount
    //     $totalAmount = $data['is_custom_amount'] ? $data['custom_amount'] : $data['amount'];
    
    //     // ✅ Setup Stripe
    //     Stripe::setApiKey(env('STRIPE_SECRET'));
    
    //     $checkoutSession = StripeSession::create([
    //         'payment_method_types' => ['card'],
    //         'line_items' => [[
    //             'price_data' => [
    //                 'currency' => $data['currency'],
    //                 'product_data' => [
    //                     'name' => 'Trip Booking for ' . $data['travel_date'],
    //                 ],
    //                 'unit_amount' => $totalAmount * 100, // amount in cents
    //             ],
    //             'quantity' => 1,
    //         ]],
    //         'mode' => 'payment',
    //         'customer_email' => $data['customer_email'],
    //         'success_url' => env('FRONTEND_URL') . '/checkout/success?session_id={CHECKOUT_SESSION_ID}',
    //         'cancel_url' => env('FRONTEND_URL') . '/checkout',
    //     ]);
    
    //     // ✅ Save payment record
    //     $order->payment()->create([
    //         'payment_status' => 'pending',
    //         'payment_method' => 'credit_card',
    //         'amount' => $data['amount'],
    //         'is_custom_amount' => $data['is_custom_amount'],
    //         'custom_amount' => $data['custom_amount'],
    //         'total_amount' => $totalAmount,
    //         'currency' => $data['currency'],
    //         'stripe_session_id' => $checkoutSession->id,
    //     ]);
    
    //     return response()->json([
    //         'id' => $checkoutSession->id,
    //         'url' => $checkoutSession->url,
    //     ]);
    // }
    

    // public function confirmPayment(Request $request)
    // {
    //     Stripe::setApiKey(env('STRIPE_SECRET'));

    //     $sessionId = $request->input('session_id');

    //     try {
    //         $session = StripeSession::retrieve($sessionId);

    //         if ($session->payment_status !== 'paid') {
    //             return response()->json(['error' => 'Payment not completed'], 400);
    //         }

    //         // ✅ Find the order_payment record using session_id
    //         $payment = OrderPayment::where('stripe_session_id', $sessionId)->first();

    //         if (!$payment) {
    //             return response()->json(['error' => 'Payment record not found'], 404);
    //         }

    //         // ✅ Update payment status
    //         $payment->update([
    //             'payment_status' => 'paid',
    //         ]);

    //         if ($payment->fresh()->payment_status === 'paid') {
    //             Order::where('id', $payment->order_id)->update([
    //                 'status' => 'success',
    //             ]);
    //         }

    //         return response()->json(['message' => 'Payment confirmed successfully']);
    //     } catch (\Exception $e) {
    //         return response()->json(['error' => 'Something went wrong: ' . $e->getMessage()], 500);
    //     }
    // }

}
