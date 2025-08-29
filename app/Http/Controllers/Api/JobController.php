<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class JobController extends Controller
{
    public function fetchJobs(Request $request)
    {
        try {
            $query = $request->input('q', 'software developer job');
            if ($request->has('location')) {
                $query .= ' in ' . $request->input('location');
            }

            $response = Http::withHeaders([
                'x-rapidapi-host' => 'jsearch.p.rapidapi.com',
                'x-rapidapi-key' => 'a007b488d3msh5fe56b4d9e822b1p1fb2bcjsnf9de8d4ed9ee', // TODO: Move to .env
            ])->get('https://jsearch.p.rapidapi.com/search', [
                'query' => $query,
                'page' => $request->input('page', 2),
                'num_pages' => $request->input('num_pages', 1),
                'country' => $request->input('gl', 'us'), // Mapped from 'gl'
                'date_posted' => $request->input('date_posted', '3days'),
            ]);



            if ($response->successful()) {
                return $response->json();
            }

            return response()->json(['error' => 'Failed to fetch jobs from external API.'], $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }
}
