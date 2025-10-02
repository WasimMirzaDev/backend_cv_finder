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
        $perPage = $request->per_page ?? 10; // Default to 10 items per page
        $page = $request->page ?? 1; // Default to first page
        
        $query = CvRecentActivity::where('user_id', Auth::user()->id)
            ->where('type', 'resume')
            ->with(['resume', 'interview']);
        
        $total = $query->count();
        $recentActivities = $query->latest()
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get()
            ->map(function ($activity) {
                $activity->unsetRelation($activity->type === 'interview' ? 'resume' : 'interview');
                return $activity;
            });
            
        return response()->json([
            'data' => $recentActivities,
            'total' => $total,
            'per_page' => (int)$perPage,
            'current_page' => (int)$page,
            'last_page' => ceil($total / $perPage)
        ]);
    }

    public function completedSteps(Request $request){
        $steps = GettingStartedStep::where('user_id', Auth::user()->id)->first();
        return response()->json($steps);
    }

    public function updateSteps(Request $request)
    {
        $steps = GettingStartedStep::where('user_id', Auth::id())->firstOrFail();
    
        $updatableFields = [
            'progress_tracker',
            'applied_job',
            'refer_friend',
        ];
    
        $dataToUpdate = [];
        foreach ($updatableFields as $field) {
            if ($request->has($field)) {
                $dataToUpdate[$field] = filter_var($request->input($field), FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            }
        }
    
        $updated = false;
        if (!empty($dataToUpdate)) {
            $steps->update($dataToUpdate);
            $updated = true;
        }
    
        return response()->json([
            'success' => $updated,
            'data' => $steps->fresh() // reload updated model
        ]);
    }
    
}
