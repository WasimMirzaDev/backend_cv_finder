<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SubscriptionController extends Controller
{
    /**
     * Step 1: Create an order (first checkout).
     */
    public function createOrder(Request $request)
    {
        if($request->customer_id){
            $response = Http::withToken(config('services.revolut_merchant.secret'))
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Revolut-Api-Version' => '2023-09-01',
            ])
            ->post(config('services.revolut_merchant.base') . '/api/orders', [
                "amount" => (int) $request->amount, // in cents
                "currency" => "USD",
                "description" => "Subscription Order",
                "save_payment_method_for" => "merchant",
                "customer" => [
                    "id" => $request->customer_id,
                    "email" => $request->email,
                ],
            ]);
        }
        else{
            $response = Http::withToken(config('services.revolut_merchant.secret'))
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Revolut-Api-Version' => '2023-09-01',
            ])
            ->post(config('services.revolut_merchant.base') . '/api/orders', [
                "amount" => (int) $request->amount, // in cents
                "currency" => "USD",
                "description" => "Subscription Order",
                "save_payment_method_for" => "merchant",
                "customer" => [
                    "email" => $request->email,
                ],
            ]);
        }
    
        return response()->json($response->json());
    }


    public function getPaymentsForOrder(Request $request)
    {
        $orderId = $request->order_id;

        $response = Http::withToken(config('services.revolut_merchant.secret'))
            ->withHeaders([
                'Accept' => 'application/json',
            ])
            ->get(config('services.revolut_merchant.base') . '/api/orders/' . $orderId . '/payments');

        return response()->json($response->json());
    }

    /**
     * Step 2: Charge later using saved payment method.
     */
    public function chargeSavedMethod(Request $request)
    {
        $orderId = $request->order_id;
        $paymentMethodId = $request->payment_method_id;

        $response = Http::withToken(config('services.revolut_merchant.secret'))
            ->post(config('services.revolut_merchant.base') . '/api/orders/' . $orderId . '/pay', [
                "saved_payment_method" => [
                    "type" => "revolut_pay",
                    "id"   => $paymentMethodId,
                    "initiator" => "merchant",
                ]
            ]);

        return response()->json($response->json());
    }

    /**
     * Step 3: Webhook listener (payment succeeded/failed).
     */
    public function webhook(Request $request)
    {
        $payload = $request->all();

        // Example handling
        if ($payload['event'] === 'ORDER_COMPLETED') {
            // Mark subscription as active in DB
        } elseif ($payload['event'] === 'ORDER_PAYMENT_DECLINED') {
            // Retry or alert user
        }

        return response()->json(['status' => 'ok']);
    }
}
