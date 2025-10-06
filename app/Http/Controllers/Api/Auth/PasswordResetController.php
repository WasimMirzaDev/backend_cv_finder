<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\PasswordReset;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\PasswordResetMail;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class PasswordResetController extends Controller
{
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $token = Str::random(60);
        $email = $request->email;

        PasswordReset::updateOrCreate(
            ['email' => $email],
            [
                'email' => $email,
                'token' => $token,
                'created_at' => now()
            ]
        );

        // Send email with reset link
        $resetUrl = config('app.frontend_url') . "/reset-password?token=" . $token . "&email=" . urlencode($email);
        Mail::to($email)->send(new PasswordResetMail($resetUrl));

        return response()->json([
            'status' => true,
            'message' => 'Password reset link sent to your email',
        ]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $passwordReset = PasswordReset::where([
            'email' => $request->email,
            'token' => $request->token
        ])->first();

        if (!$passwordReset) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid or expired token',
            ], 400);
        }

        // Check if token is expired (24 hours)
        if (Carbon::parse($passwordReset->created_at)->addHours(24)->isPast()) {
            $passwordReset->delete();
            return response()->json([
                'status' => false,
                'message' => 'Token has expired',
            ], 400);
        }

        // Update user password
        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // Delete the token
        $passwordReset->delete();

        return response()->json([
            'status' => true,
            'message' => 'Password has been reset successfully',
        ]);
    }

    public function validateToken(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string'
        ]);

        $passwordReset = PasswordReset::where([
            'email' => $request->email,
            'token' => $request->token
        ])->first();

        if (!$passwordReset) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid or expired token',
            ], 400);
        }

        if (Carbon::parse($passwordReset->created_at)->addHours(24)->isPast()) {
            $passwordReset->delete();
            return response()->json([
                'status' => false,
                'message' => 'Token has expired',
            ], 400);
        }

        return response()->json([
            'status' => true,
            'message' => 'Token is valid',
        ]);
    }
}
