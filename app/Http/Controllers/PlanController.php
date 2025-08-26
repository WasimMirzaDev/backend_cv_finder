<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Plan::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'subdesc' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'interval' => 'required|string|in:monthly,yearly,weekly,daily,quarterly',
            'features' => 'nullable|array',
        ]);

        $plan = Plan::create($validatedData);

        return response()->json($plan, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Plan $plan)
    {
        return $plan;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Plan $plan)
    {
        $validatedData = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'subdesc' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'interval' => 'sometimes|required|string|in:monthly,yearly,weekly,daily,quarterly',
            'features' => 'nullable|array',
        ]);

        $plan->update($validatedData);

        return response()->json($plan);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Plan $plan)
    {
        $plan->delete();

        return response()->json(null, 204);
    }
}
