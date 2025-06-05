<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret = env('STRIPE_WEBHOOK_SECRET');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sigHeader, $secret
            );
        } catch (\UnexpectedValueException $e) {
            return response()->json(['message' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        // Handle different events
        if ($event->type === 'payment_intent.succeeded') {
            $intent = $event->data->object;

            $orderId = $intent->metadata->order_id ?? null;
            if ($orderId) {
                $order = Order::find($orderId);
                if ($order) {
                    $order->status = 'confirmed';
                    $order->save();

                    $payment = $order->payment;
                    if ($payment) {
                        $payment->payment_status = 'confirmed';
                        $payment->save();
                    }
                }
            }
        }

        if ($event->type === 'payment_intent.payment_failed') {
            $intent = $event->data->object;
            $orderId = $intent->metadata->order_id ?? null;
            if ($orderId) {
                $order = Order::find($orderId);
                if ($order) {
                    $order->status = 'cancelled';
                    $order->save();

                    $payment = $order->payment;
                    if ($payment) {
                        $payment->payment_status = 'failed';
                        $payment->save();
                    }
                }
            }
        }

        return response()->json(['message' => 'Webhook received']);
    }
}
