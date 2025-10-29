<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Str;

class FirebaseController extends Controller
{
    public function authenticate(Request $request, \Kreait\Firebase\Auth $firebaseAuth)
    {
        $idToken = $request->input('idToken');
        if (!$idToken) {
            return response()->json(['error'=>'No ID token provided'], 422);
        }

        try {
            // Verify the Firebase ID token
            $verifiedToken = $firebaseAuth->verifyIdToken($idToken);
            $uid = $verifiedToken->claims()->get('sub'); // firebase uid
            $email = $verifiedToken->claims()->get('email');
            $name = $verifiedToken->claims()->get('name') ?? $email;

            // Find or create local user
            $user = User::firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => bcrypt(Str::random(40))]
            );

            // Issue Laravel token (Sanctum / personal access token)
            $token = $user->createToken('web')->plainTextToken;

            return response()->json([
                'user' => $user,
                'token' => $token
            ]);
        } catch (\Kreait\Firebase\Exception\Auth\FailedToVerifyToken $e) {
            return response()->json(['error' => 'Invalid ID token: '.$e->getMessage()], 401);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Server error: '.$e->getMessage()], 500);
        }
    }
}
