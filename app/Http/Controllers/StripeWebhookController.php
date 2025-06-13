<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderPayment;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret = env('STRIPE_WEBHOOK_SECRET');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\UnexpectedValueException $e) {
            Log::error('Stripe Webhook Error: Invalid payload');
            return response()->json(['message' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe Webhook Error: Invalid signature');
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        $eventType = $event->type;
        Log::info("Stripe Webhook Received: $eventType");

        $intent = $event->data->object;
        $orderId = $intent->metadata->order_id ?? null;

        if (!$orderId) {
            Log::warning('Stripe Webhook: No order_id in metadata');
            return response()->json(['message' => 'No order ID'], 200);
        }

        $order = Order::find($orderId);
        if (!$order) {
            Log::warning("Stripe Webhook: Order not found for ID $orderId");
            return response()->json(['message' => 'Order not found'], 200);
        }

        $payment = $order->payment;

        if ($eventType === 'payment_intent.succeeded') {
            $order->status = 'confirmed';
            if ($payment) {
                $payment->payment_status = 'confirmed';
                $payment->save();
            }
            $order->save();
            Log::info("Order #$orderId confirmed");
        }

        if ($eventType === 'payment_intent.payment_failed') {
            $order->status = 'cancelled';
            if ($payment) {
                $payment->payment_status = 'failed';
                $payment->save();
            }
            $order->save();
            Log::info("Order #$orderId cancelled due to payment failure");
        }

        return response()->json(['message' => 'Webhook processed']);
    }

    // public function handleWebhook(Request $request)
    // {
    //     $payload = $request->getContent();
    //     $sigHeader = $request->header('Stripe-Signature');
    //     $secret = env('STRIPE_WEBHOOK_SECRET');

    //     try {
    //         $event = \Stripe\Webhook::constructEvent(
    //             $payload, $sigHeader, $secret
    //         );
    //     } catch (\UnexpectedValueException $e) {
    //         return response()->json(['message' => 'Invalid payload'], 400);
    //     } catch (\Stripe\Exception\SignatureVerificationException $e) {
    //         return response()->json(['message' => 'Invalid signature'], 400);
    //     }

    //     // Handle different events
    //     if ($event->type === 'payment_intent.succeeded') {
    //         $intent = $event->data->object;

    //         $orderId = $intent->metadata->order_id ?? null;
    //         if ($orderId) {
    //             $order = Order::find($orderId);
    //             if ($order) {
    //                 $order->status = 'confirmed';
    //                 $order->save();

    //                 $payment = $order->payment;
    //                 if ($payment) {
    //                     $payment->payment_status = 'confirmed';
    //                     $payment->save();
    //                 }
    //             }
    //         }
    //     }

    //     if ($event->type === 'payment_intent.payment_failed') {
    //         $intent = $event->data->object;
    //         $orderId = $intent->metadata->order_id ?? null;
    //         if ($orderId) {
    //             $order = Order::find($orderId);
    //             if ($order) {
    //                 $order->status = 'cancelled';
    //                 $order->save();

    //                 $payment = $order->payment;
    //                 if ($payment) {
    //                     $payment->payment_status = 'failed';
    //                     $payment->save();
    //                 }
    //             }
    //         }
    //     }

    //     return response()->json(['message' => 'Webhook received']);
    // }
}
