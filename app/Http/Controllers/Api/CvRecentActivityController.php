<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\CvRecentActivity;
use App\Models\GettingStartedStep;

class CvRecentActivityController extends Controller
{
    public function index(Request $request)
    {
        $recentActivities = CvRecentActivity::where('user_id', Auth::user()->id)
            ->with(['resume', 'interview'])
            ->latest()
            ->take($request->limit ?? 3)
            ->get()
            ->map(function ($activity) {
                $activity->unsetRelation($activity->type === 'interview' ? 'resume' : 'interview');
                return $activity;
            });
            
        return response()->json($recentActivities);
    }

    public function recentCreatedCv(Request $request)
    {
        $recentActivities = CvRecentActivity::where('user_id', Auth::user()->id)
            ->where('type','resume')
            ->with(['resume', 'interview'])
            ->latest()
            ->take($request->limit ?? 3)
            ->get()
            ->map(function ($activity) {
                $activity->unsetRelation($activity->type === 'interview' ? 'resume' : 'interview');
                return $activity;
            });
            
        return response()->json($recentActivities);
    }

    public function completedSteps(Request $request){
        $steps = GettingStartedStep::where('user_id', Auth::user()->id)->first();
        return response()->json($steps);
    }

    public function updateSteps(Request $request){
        $steps = GettingStartedStep::where('user_id', Auth::user()->id)->firstOrFail();
        
        // Update each field that exists in the request
        $updatableFields = [
            'progress_tracker',
            'applied_job',
            'refer_friend',
        ];
        
        $updated = false;
        foreach ($updatableFields as $field) {
            if ($request->has($field)) {
                $steps->$field = $request->input($field);
                $updated = true;
            }
        }
        
        if ($updated) {
            $steps->save();
        }
        
        return response()->json([
            'success' => $updated,
            'data' => $steps
        ]);
    }
}
