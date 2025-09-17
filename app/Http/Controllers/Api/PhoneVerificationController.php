<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Services\TwilioService;
use App\Models\PendingUser;

class PhoneVerificationController extends Controller
{
    protected $twilio;

    public function __construct(TwilioService $twilio)
    {
        $this->twilio = $twilio;
    }

    // Step 1: Send OTP
    public function sendOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string'
        ]);

        $verification = $this->twilio->sendVerification($request->phone);

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully',
            'sid' => $verification->sid
        ]);
    }

    // Step 2: Verify OTP
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'otp'   => 'required|string'
        ]);

        $check = $this->twilio->checkVerification($request->phone, $request->otp);

        if ($check->status === "approved") {
            // ✅ Mark user as verified
            $user = PendingUser::where('phone', $request->phone)->first();
            if ($user) {
                $user->phone_verified_at = now();
                $user->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Phone number verified successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid OTP'
        ], 400);
    }
}
