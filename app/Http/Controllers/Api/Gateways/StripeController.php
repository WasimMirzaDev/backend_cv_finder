<?php

namespace App\Http\Controllers\Api\Gateways;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Illuminate\Support\Facades\Auth;
use App\Models\Plan;
use App\Models\Subscription;

class StripeController extends Controller
{



    public function getSubscriptionDetails(Request $request)
    {
        $user = Auth::user();

        $subscription = Subscription::where('user_id', $user->id)
            ->with('plan')
            ->latest()
            ->first();

        if (!$subscription || !$subscription->type_id) {
            return response()->json(['message' => 'Active subscription not found.'], 404);
        }

        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

        try {
            // $subscriptionStripe = \Stripe\Subscription::retrieve([
            //     'id' => $subscription->sub_id,
            //     'expand' => []
            // ]);

            return response()->json([
                'subscription' => $subscription,
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve subscription details.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function createSubscriptionSession(Request $request, $planId)
    {
        $plan = Plan::find($planId);
        if (!$plan) {
            return response()->json([
                'error' => 'Plan not found',
            ], 404);
        }

        Stripe::setApiKey(env('STRIPE_SECRET'));

        if($request->isFreeTrial){
            $session = Session::create([
                'payment_method_types' => ['card'],
                'mode' => 'subscription',
                'line_items' => [[
                    'price' => $plan->stripe_price_id,
                    'quantity' => 1,
                ]],
                'subscription_data' => [
                    'trial_period_days' => 7,
                    'metadata' => [
                        'type' => 'subscription',
                        'user_id' => Auth::id() ?? null,
                    ],
                ],
                'customer_email' => Auth::user()->email ?? '',
                'success_url' => 'https://lightgreen-duck-722360.hostingersite.com/payment-success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => 'https://lightgreen-duck-722360.hostingersite.com/payment-cancelled',
            ]);
    
            return response()->json([
                'sessionId' => $session->id,
                'checkoutUrl' => $session->url,
            ]);
        }
        else{

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
                'success_url' => 'https://lightgreen-duck-722360.hostingersite.com/payment-success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => 'https://lightgreen-duck-722360.hostingersite.com/payment-cancelled',
            ]);
    
            return response()->json([
                'sessionId' => $session->id,
                'checkoutUrl' => $session->url,
            ]);
        }


       
    }

    public function cancelSubscription(Request $request)
{
    $user = Auth::user();

    $subscription = Subscription::where('user_id', $user->id)
        ->latest()
        ->first();

    if (!$subscription || !$subscription->sub_id) {
        return response()->json(['message' => 'Active subscription not found.'], 404);
    }

    \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

    try {
        // Cancel at period end
        \Stripe\Subscription::update(
            $subscription->sub_id,
            ['cancel_at_period_end' => true]
        );

           $subscriptionStripe =  \Stripe\Subscription::retrieve([
                'id' => $subscription->sub_id,
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

    public function getPaymentMethod(Request $request)
    {
        $user = Auth::user();

        $subscription = Subscription::where('user_id', $user->id)
            ->latest()
            ->first();

        if (!$subscription || !$subscription->cus_id) {
            return response()->json(['message' => 'Active subscription not found.'], 404);
        }

        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

        
        try {
            $customer = \Stripe\Customer::retrieve($subscription->cus_id);
            
            // Get default payment method ID
            $defaultPaymentMethodId = $customer->invoice_settings->default_payment_method;

            // return $defaultPaymentMethodId;
            $paymentMethods = \Stripe\PaymentMethod::all([
                'customer' => $subscription->cus_id,
                'type' => 'card',
            ]);            
            foreach ($paymentMethods->data as $pm) {
                $isDefault = ($pm->id == $defaultPaymentMethodId);
                $pm->default = $isDefault;
            }
            return response()->json($paymentMethods);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve payment method.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deletePaymentMethod(Request $request, $id)
    {
        $user = Auth::user();

        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

        try {
            $paymentMethod = \Stripe\PaymentMethod::retrieve($id);
            $paymentMethod->detach();

            return response()->json(['message' => 'Payment method deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete payment method.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function createSetupIntent($customerId)
    {
    \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

    $intent = \Stripe\SetupIntent::create([
        'customer' => $customerId,
        'payment_method_types' => ['card'],
    ]);

    return response()->json([
        'clientSecret' => $intent->client_secret,
    ]);
   }

   public function makeDefaultPaymentMethod(Request $request, $customerId)
   {

    \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

    \Stripe\Customer::update($customerId, [
        'invoice_settings' => [
            'default_payment_method' => $request->payment_method_id,
        ],
    ]);   

    return response()->json(['message' => 'Payment method updated successfully.']);
    }

    public function changePlan(Request $request, $newPriceId)
    {
        $user = Auth::user();
        $subscription = Subscription::where('user_id', $user->id)
            ->latest()
            ->first();
        
        $subscriptionId = $subscription->sub_id;

        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
        // Retrieve the current subscription
        $subscription = \Stripe\Subscription::retrieve($subscriptionId);
    
        // Update the subscription with new plan
        $updated = \Stripe\Subscription::update($subscriptionId, [
            'items' => [[
                'id' => $subscription->items->data[0]->id, // keep same item
                'price' => $newPriceId, // new plan price ID
            ]],
            'proration_behavior' => 'create_prorations', // immediate adjustment
        ]);
    
        return $updated;
    }
}