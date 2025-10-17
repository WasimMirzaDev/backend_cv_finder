<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subcategory;
use App\Models\Role;
use App\Models\EducationLevel;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function getUser(Request $request)
    {
        $user = $request->user();
        return response()->json($user);
    }

    public function getIndustries(Request $request) 
    {
        $industries = Subcategory::where('questiontype_id', 1)->orWhere('questiontype_id', 2)->get();
        return response()->json($industries);
    }

    public function getRoles(Request $request) 
    {
        $roles = Role::all();
        return response()->json($roles);
    }

    public function getEducationLevels(Request $request) 
    {
        $educationLevels = EducationLevel::all();
        return response()->json($educationLevels);
    }

    public function uploadProfile(Request $request)
    {
        $request->validate([
            'industry_id' => 'sometimes|exists:cv_subcategories,id',
            'role_id' => 'sometimes|exists:cv_roles,id',
            'education_level_id' => 'sometimes|exists:cv_education_levels,id',
            'linkedin_profile_url' => 'nullable|url',
        ]);
        $user = Auth::user();
        $user->preferred_industry_id = $request->industry_id ?? null;
        $user->role_id = $request->role_id ?? null;
        $user->education_level_id = $request->education_level_id ?? null;
        $user->linkedin_profile_url = $request->linkedin_profile_url ?? null;
        $user->save();
        return response()->json([
            'status' => true,
            'message' => 'Profile updated successfully',
            'user' => $user,
        ]);
    }

    public function ProfileSettings(Request $request){
        $request->validate([
            "name" => "sometimes|string|max:100|min:3",
            "phone" => "sometimes|string|max:200",
            "email" => "sometimes|email|unique:users,email," . auth()->id(),
            "profile_img" => "sometimes|image|mimes:jpeg,png,jpg,gif|max:2048",
            "bio" => "nullable|string|max:300",
            "lang" => "sometimes|string|max:10",
            "time_zone" => "sometimes|string|max:50",
            "email_notif" => "sometimes|boolean",
            "push_notif" => "sometimes|boolean"
        ]);

        $user = Auth::user();
        
        // Update basic info
        if ($request->has('name')) {
            $user->name = $request->name;
        }
        
        // if ($request->has('address')) {
        //     $user->address = $request->address;
        // }
        
        if ($request->has('phone')) {
            $user->phone = $request->phone;
        }
        
        if ($request->has('bio')) {
            $user->bio = $request->bio;
        }
        
        if ($request->has('email') && $request->email !== $user->email) {
            $user->email = $request->email;
            // You might want to implement email verification here
        }

        // Update language preference
        if ($request->has('lang')) {
            $user->lang = $request->lang;
        }

        // Update timezone
        if ($request->has('time_zone')) {
            $user->time_zone = $request->time_zone;
        }

        // Update email notification preference
        if ($request->has('email_notif')) {
            $user->email_notif = $request->email_notif;
        }

        // Update push notification preference
        if ($request->has('push_notif')) {
            $user->push_notif = $request->push_notif;
        }

        // Handle profile image upload
        if ($request->hasFile('profile_img')) {
            // Create profiles directory if it doesn't exist
            $publicPath = public_path('profiles');
            if (!file_exists($publicPath)) {
                mkdir($publicPath, 0777, true);
            }
            
            // Delete old profile image if exists
            if ($user->profile_img) {
                $oldImagePath = public_path($user->profile_img);
                if (file_exists($oldImagePath) && strpos($oldImagePath, '/profiles/') !== false) {
                    @unlink($oldImagePath);
                }
            }
            
            // Generate unique filename
            $filename = 'profile_' . time() . '.' . $request->file('profile_img')->getClientOriginalExtension();
            
            // Move the file to public/profiles
            $request->file('profile_img')->move($publicPath, $filename);
            
            // Store the relative path
            $user->profile_img = '/profiles/' . $filename;
        }

        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Profile updated successfully',
            'user' => $user->fresh()
        ]);
}
}