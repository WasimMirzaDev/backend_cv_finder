<?php

namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class TwilioService
{
    protected $client;
    protected $verifySid;

    public function __construct()
    {
        $accountSid = config('services.twilio.sid') ?: env('TWILIO_SID');
        $authToken = config('services.twilio.token') ?: env('TWILIO_AUTH_TOKEN');
        $this->verifySid = config('services.twilio.verify_sid') ?: env('TWILIO_VERIFY_SID');

        if (empty($accountSid) || empty($authToken) || empty($this->verifySid)) {
            Log::error('Missing Twilio configuration', [
                'has_account_sid' => !empty($accountSid),
                'has_auth_token' => !empty($authToken),
                'has_verify_sid' => !empty($this->verifySid),
            ]);
            throw new \RuntimeException('Twilio service configuration is incomplete');
        }

        $this->client = new Client($accountSid, $authToken);
    }

    public function sendVerification($phoneNumber)
    {
        try {
            if (empty($this->verifySid)) {
                throw new \RuntimeException('Twilio Verify SID is not configured');
            }
    
            // For development/testing or blocked regions
            if (app()->environment('local', 'testing') || config('services.twilio.force_mock', false)) {
                \Log::info('Using mock Twilio verification', ['phone' => $phoneNumber]);
                return (object)[
                    'sid' => 'VE' . md5($phoneNumber . time()),
                    'status' => 'pending',
                    'to' => $phoneNumber,
                ];
            }
    
            \Log::info('Sending Twilio verification', [
                'to' => $phoneNumber,
                'verify_sid' => $this->verifySid
            ]);
    
            return $this->client->verify->v2->services($this->verifySid)
                ->verifications
                ->create($phoneNumber, "sms");
    
        } catch (\Exception $e) {
            \Log::error('Twilio verification error', [
                'error' => $e->getMessage(),
                'phone' => $phoneNumber,
                'verify_sid' => $this->verifySid
            ]);
            
            // If there's an error, return a mock response in development
            if (app()->environment('local', 'testing') || config('services.twilio.force_mock', false)) {
                return (object)[
                    'sid' => 'VE' . md5($phoneNumber . time()),
                    'status' => 'pending',
                    'to' => $phoneNumber,
                ];
            }
            
            throw $e;
        }
    }

    public function checkVerification($phoneNumber, $code)
    {
        try {
            if (empty($this->verifySid)) {
                throw new \RuntimeException('Twilio Verify SID is not configured');
            }

            return $this->client->verify->v2->services($this->verifySid)
                ->verificationChecks
                ->create([
                    'to' => $phoneNumber,
                    'code' => $code,
                ]);
        } catch (\Exception $e) {
            Log::error('Twilio verification check error', [
                'error' => $e->getMessage(),
                'phone' => $phoneNumber,
                'code' => $code
            ]);
            throw $e;
        }
    }
}