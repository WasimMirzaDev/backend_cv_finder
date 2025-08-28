<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RevolutToken;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class RevolutController extends Controller
{
    public function accounts()
    {
        $accessToken = $this->getValidAccessToken();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->get('https://sandbox-b2b.revolut.com/api/1.0/accounts');

        return response()->json($response->json());
    }

    private function getValidAccessToken()
    {
        $token = RevolutToken::latest()->first();

        // If token exists and is fresh (less than 35 minutes old), return it.
        if ($token && $token->expires_at->subMinutes(5) < Carbon::now()) {
            return $token->access_token;
        }

        // If token exists but is expired, refresh it.
        if ($token) {
            return $this->refreshToken($token);
        }

        // If no token exists, get a new one.
    }

    private function refreshToken($token)
    {
        $response = Http::asForm()->post('https://sandbox-b2b.revolut.com/api/1.0/auth/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $token->refresh_token,
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => $this->getClientAssertion(), // build your signed JWT here
        ]);
    
        if ($response->failed()) {
            throw new \Exception('Failed to refresh Revolut token: ' . $response->body());
        }
    
        $newAccessToken = $response->json();
    
        // Update DB token record
        $token->update([
            'access_token'  => $newAccessToken['access_token'],
            'refresh_token' => $newAccessToken['refresh_token'] ?? $token->refresh_token,
            'expires_at'    => now()->addSeconds($newAccessToken['expires_in']),
        ]);
    
        return $newAccessToken['access_token'];
    }
    

    private function getClientAssertion()
    {
        $clientAssertion = public_path('jwt_token.txt');

        if (!file_exists($clientAssertion)) {
            throw new \Exception('Private key file not found at: ' . $clientAssertion);
        }

        $privateKey = file_get_contents($clientAssertion);

        if (empty($privateKey)) {
            throw new \Exception('Private key file is empty.');
        }

        return $privateKey;
    }


    public function createPayoutLink(Request $request)
    {
        $accessToken = $this->getValidAccessToken();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post('https://sandbox-b2b.revolut.com/api/1.0/payout-links', [
            'counterparty_name' => $request->input('counterparty_name', 'John Smith'),
            'save_counterparty' => $request->input('save_counterparty', false),
            'request_id' => uniqid(),
            'account_id' => env('REVOLUT_ACCOUNT_ID'), // Make sure to set this in your .env file
            'amount' => $request->input('amount', 105.6),
            'currency' => $request->input('currency', 'GBP'),
            'reference' => $request->input('reference', 'Rent'),
            'payout_methods' => $request->input('payout_methods', ['revolut', 'bank_account', 'card']),
            'expiry_period' => $request->input('expiry_period', 'P3D'),
            'transfer_reason_code' => $request->input('transfer_reason_code', 'property_rental'),
        ]);

        return response()->json($response->json(), $response->status());
    }
    


}
