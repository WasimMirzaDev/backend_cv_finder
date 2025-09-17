<?php

namespace App\Services;

use Twilio\Rest\Client;

class TwilioService
{
    protected $client;
    protected $verifySid;

    public function __construct()
    {
        $this->client = new Client(env('TWILIO_SID'), env('TWILIO_AUTH_TOKEN'));
        $this->verifySid = env('TWILIO_VERIFY_SID');
    }

    public function sendVerification($phoneNumber)
    {
        return $this->client->verify->v2->services($this->verifySid)
            ->verifications
            ->create($phoneNumber, "sms");
    }

    public function checkVerification($phoneNumber, $code)
    {
        return $this->client->verify->v2->services($this->verifySid)
            ->verificationChecks
            ->create([
                'to' => $phoneNumber,
                'code' => $code,
            ]);
    }
}
