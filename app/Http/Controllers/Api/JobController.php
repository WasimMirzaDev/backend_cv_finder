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
            $response = Http::withHeaders([
                'X-API-KEY' => '005f8ef3c4798d43c893c97f43a75cbafba428d4', // Consider moving this to .env
                'Content-Type' => 'application/json',
            ])->post('https://google.serper.dev/search', [
                'q' => $request->input('q', 'software developer job'), // Default query
                'location' => $request->input('location'),
                'gl' => $request->input('gl', 'us'),
                'num' => $request->input('num', 10),
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
