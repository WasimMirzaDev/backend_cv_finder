<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\CvRecentActivity;

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
}
