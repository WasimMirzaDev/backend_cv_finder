<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Services\FirebaseTokenVerifier;
use Illuminate\Support\Str;
use App\Models\GettingStartedStep;

class FirebaseController extends Controller
{
    protected $tokenVerifier;

    public function __construct(FirebaseTokenVerifier $tokenVerifier)
    {
        $this->tokenVerifier = $tokenVerifier;
    }

    public function authenticate(Request $request)
    {
        $idToken = $request->input('idToken');
        if (!$idToken) {
            return response()->json(['error' => 'No ID token provided'], 422);
        }

        try {
            // Verify the Firebase ID token using our custom verifier
            $decodedToken = $this->tokenVerifier->verifyToken($idToken);
            
            $uid = $decodedToken['sub']; // Firebase UID
            $email = $decodedToken['email'];
            $name = $decodedToken['name'] ?? ($decodedToken['email'] ?? 'User');

            // Find or create local user
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => bcrypt(Str::random(40)),
                    'firebase_uid' => $uid
                ]
            );

            $steps = GettingStartedStep::create([
            'user_id' => $user->id,
            'sign_up' => true,
            ]);

            // Revoke existing tokens and create a new one
            $user->tokens()->delete();
            $token = $user->createToken('web')->plainTextToken;

            return response()->json([
                'user' => $user,
                'token' => $token
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Authentication failed: ' . $e->getMessage()], 401);
        }
    }
}
