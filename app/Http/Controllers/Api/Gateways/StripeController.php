<?php

namespace App\Http\Controllers\Api\Gateways;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Illuminate\Support\Facades\Auth;
use App\Models\Plan;

class StripeController extends Controller
{
    public function createSubscriptionSession(Request $request, $planId)
    {
        $plan = Plan::find($planId);
        if (!$plan) {
            return response()->json([
                'error' => 'Plan not found',
            ], 404);
        }
        Stripe::setApiKey(env('STRIPE_SECRET'));

        $session = Session::create([
            'payment_method_types' => ['card'],
            'mode' => 'subscription',
            'line_items' => [[
                'price' => $plan->stripe_price_id,
                'quantity' => 1,
            ]],
            'subscription_data' => [
                'metadata' => [
                    'type' => 'subscription',
                    'user_id' => Auth::id() ?? null,
                ],
            ],
            'customer_email' => Auth::user()->email ?? '',
            'success_url' => config('app.url') . '/payment-success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => config('app.url') . '/payment-cancelled',
        ]);

        return response()->json([
            'sessionId' => $session->id,
            'checkoutUrl' => $session->url,
        ]);
    }

    public function cancelSubscription(Request $request)
{
    $user = Auth::user();

    $subscription = Subscription::where('user_id', $user->id)
        ->latest()
        ->first();

    if (!$subscription || !$subscription->type_id) {
        return response()->json(['message' => 'Active subscription not found.'], 404);
    }

    \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

    try {
        // Cancel at period end
        \Stripe\Subscription::update(
            $subscription->type_id,
            ['cancel_at_period_end' => true]
        );

           $subscriptionStripe =  \Stripe\Subscription::retrieve([
                'id' => $subscription->type_id,
                'expand' => []
            ]);

        // Optional: record that it will end later
        $subscription->ends_at = \Carbon\Carbon::createFromTimestamp(
            $subscriptionStripe->cancel_at
        );
        $subscription->save();

        return response()->json(['message' => 'Subscription will be cancelled at period end.']);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Failed to cancel subscription.',
            'error' => $e->getMessage()
        ], 500);
    }
}
}
