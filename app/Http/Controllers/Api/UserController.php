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
}
