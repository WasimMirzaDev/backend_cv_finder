<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PendingUser;
use App\Models\GettingStartedStep;
use App\Services\TwilioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{   

    protected $twilio;

    public function __construct(TwilioService $twilio)
    {
        $this->twilio = $twilio;
    }


    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|min:3',
            'phone' => 'required|string|max:40|min:2|unique:users',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|min:8|max:18|confirmed',
        ]);
        
        $verification = $this->twilio->sendVerification($request->phone);
        return response()->json([
            'status' => true,
            'verification' => $verification,
        ], 201);
        $user = User::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        
        
        $steps = GettingStartedStep::create([
            'user_id' => $user->id,
            'sign_up' => true,
        ]);
        

        return response()->json([
            'status' => true,
            'verification_required' => true,
            'message' => 'OTP sent successfully.',
            // 'sid' => $verification->sid,
            'user' => $user,
            'access_token' => $user->createToken('auth_token')->plainTextToken,
            'token_type' => 'Bearer',
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|max:100',
            'password' => 'required|string|min:8|max:18',
        ]);

        if (!Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        $user = Auth::user();

        return response()->json([
            'status' => true,
            'message' => 'User logged in successfully',
            'user' => $user,
            'access_token' => $user->createToken('auth_token')->plainTextToken,
            'token_type' => 'Bearer',
        ]);
    }


    public function changeCurrentPassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string|min:8',
            'new_password' => 'required|string|min:8|confirmed|different:current_password',
        ]);
    
        $user = Auth::user();
    
        // Check if current password matches
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'The current password is incorrect.'
            ], 422);
        }
    
        // Update the password
        $user->password = Hash::make($request->new_password);
        $user->save();
    
        // Revoke all tokens (optional: log out all devices)
        // $user->tokens()->delete();
    
        return response()->json([
            'status' => true,
            'message' => 'Password updated successfully.'
        ]);
    }
}
