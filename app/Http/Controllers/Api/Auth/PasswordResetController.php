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
        try {
            $validated = $request->validate([
                'email' => 'required|email|exists:users,email'
            ]);
    
            $token = Str::random(60);
            $email = $validated['email'];
    
            // Delete any existing tokens for this email
            PasswordReset::where('email', $email)->delete();
    
            // Create new token
            $passwordReset = PasswordReset::create([
                'email' => $email,
                'token' => $token,
                'created_at' => now()
            ]);
    
            // Send email with reset link
            $resetUrl = config('app.frontend_url') . "/reset-password?token=" . $token . "&email=" . urlencode($email);
            
            try {
                Mail::to($email)->send(new PasswordResetMail($resetUrl));
            } catch (\Exception $e) {
                \Log::error('Failed to send password reset email: ' . $e->getMessage());
                return response()->json([
                    'status' => false,
                    'message' => 'Failed to send password reset email. Please try again later.'
                ], 500);
            }
    
            return response()->json([
                'status' => true,
                'message' => 'Password reset link has been sent to your email address.'
            ]);
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Password reset error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while processing your request.'
            ], 500);
        }
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
