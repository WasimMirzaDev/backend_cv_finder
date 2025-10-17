<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use App\Models\JobApplication;
use App\Models\GettingStartedStep;

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
                // 'x-rapidapi-key' => 'a007b488d3msh5fe56b4d9e822b1p1fb2bcjsnf9de8d4ed9ee', // TODO: Move to .env
                'x-rapidapi-key' => '5c3bd1c2b6msh6bc0f0900adf47dp14f5c6jsn80743d5bec82',
            ])->get('https://jsearch.p.rapidapi.com/search', [
                'query' => $query,
                'page' => $request->input('page', 1),
                'num_pages' => $request->input('num_pages', 3),
                'country' => $request->input('gl', 'uk'), // Mapped from 'gl'
                'date_posted' => $request->input('date_posted', 'week'),
                'work_from_home' => $request->input('remote', 'false'),
            ]);

           $data = $response->json();
           
           $prompt_jobs = [];
           foreach ($data['data'] as $job) {
            $prompt_jobs[] = [
                'job_id' => $job['job_id'],
                'job_title' => $job['job_title'],
                'job_description' => $job['job_description'],
                'job_salary' => $job['job_salary'] ?? null,
                'job_max_salary' => $job['job_max_salary'] ?? null,
                'job_min_salary' => $job['job_min_salary'] ?? null,
                'job_salary_period' => $job['job_salary_period'] ?? null,
                'job_highlights' => $job['job_highlights']['Benefits'] ?? null,
            ];
           }

            try {
                $model = 'gpt-4o-mini';
                $apiKey = config('services.openai.api_key');
            
                // Construct detailed evaluation prompt based on the framework
                $prompt = "You are a professional job data parser. Your task is to extract and standardize salary information from job postings. Analyze the provided job data to find any salary information.

                **Job Data to Analyze:**
                " . json_encode($prompt_jobs, JSON_PRETTY_PRINT) . "
            
                **Output Requirements:**
                Return a JSON object with the following structure:
                {
                  \"job_id\": \"original_job_id\",
                  \"salary_found\": boolean,
                  \"salary_data\": {
                    \"currency\": \"GBP\",
                    \"min_amount\": number (null if not found),
                    \"max_amount\": number (null if not found),
                    \"period\": \"yearly\",
                  },
                }
            
                **Salary Conversion Rules:**
                1. Convert all salaries to yearly amounts !important
                2. hourly: ×2080, weekly: ×52, monthly: ×12 !important
                3. Extract both min and max if range is given
                4. Default to GBP if £ symbol is used
            
                Now process the above job data and return ONLY the JSON output:";
    
                $gptResponse = Http::timeout(90)->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type' => 'application/json',
                ])->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model, // Dynamic model selection
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a professional JSON job parser. Respond ONLY with valid full JSON.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'temperature' => 0.0, // Minimize randomness
                    'response_format' => ['type' => 'json_object'], // Ensure JSON output
                    'max_tokens' => 2048, // Allow for detailed evaluation
                ]);
    
            
                $evaluation = $gptResponse->json()['choices'][0]['message']['content'] ?? null;
                $parsedData = json_decode($evaluation, true);
    
                if (isset($evaluation)) {
                    $aiText = $evaluation;
    
                    // Extract only the JSON part
                    $jsonStart = strpos($aiText, '{');
                    if ($jsonStart !== false) {
                        $jsonString = substr($aiText, $jsonStart);
                        $decoded = json_decode($jsonString, true);

    
                        if (json_last_error() === JSON_ERROR_NONE && isset($decoded['jobs'])) {
                            return response()->json([
                                'success' => true,
                                'data' => $decoded,
                                'jobs' => $data['data']
                            ]);
                        }
    
                        return response()->json([
                            'error' => 'Invalid JSON from GPT-4o',
                            'raw' => $aiText,
                        ], 500);
                    }
    
                    return response()->json([
                        'error' => 'No JSON found in GPT-4o response',
                        'raw' => $aiText,
                    ], 500);
                }
    
                // return $parsedData;
    
                // if ($gptResponse->json()) {
                //     $pdfParsed = new PdfParsed();
                //     $pdfParsed->ip_address = $request->ip();
                //     $pdfParsed->user_agent = $request->userAgent();
                //     if (isset($parsedData['data']['candidateName'][0]['firstName'], $parsedData['data']['candidateName'][0]['familyName'])) {
                //         $pdfParsed->full_name = $parsedData['data']['candidateName'][0]['firstName'] . ' ' . $parsedData['data']['candidateName'][0]['familyName'];
                //     }
                //     $pdfParsed->file_name = $file->getClientOriginalName();
                //     $pdfParsed->parsed_data = $parsedData;
                //     $pdfParsed->save();
                //     return response()->json([
                //         'success' => true,
                //         'data' => $parsedData
                //     ]);
                // }
    
                
            } catch (\Exception $e) {
                return response()->json(['error' => 'Failed to get job from AI model', 'details' => $e->getMessage()], 500);
            }



            if ($response->successful()) {
                return $response->json();
            }

            return response()->json(['error' => 'Failed to fetch jobs from external API.'], $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Apply for a job
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function applyJob(Request $request)
    {
        try {
            $validated = $request->validate([
                'job' => 'required|array',
                'title' => 'required|string|max:255',
                'company' => 'required|string|max:255',
                'cv_created' => 'sometimes|boolean',
                'interview_practice' => 'sometimes|boolean',
                'applied' => 'sometimes|boolean',
                'status' => 'sometimes|in:prep,appSent,shortListed,1stInterview,2ndInterview,finalInterview,onHold,OfferAcctepted,UnSuccessful'
            ]);

            // Create a new application
            $application = JobApplication::create([
                'job' => $validated['job'],
                'title' => $validated['title'],
                'company' => $validated['company'],
                'cv_created' => $validated['cv_created'] ?? false,
                'interview_practice' => $validated['interview_practice'] ?? false,
                'applied' => $validated['applied'] ?? false,
                'status' => $validated['status'] ?? 'prep',
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'data' => $application
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create job application',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get authenticated user's job applications
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function appliedJobs(Request $request)
    {
        try {
            $limit = $request->input('limit', 6);
            
            $jobs = JobApplication::where('user_id', Auth::id())
                ->latest()
                ->paginate($limit);

            return response()->json([
                'success' => true,
                'data' => $jobs->items(),
                'pagination' => [
                    'total' => $jobs->total(),
                    'per_page' => $jobs->perPage(),
                    'current_page' => $jobs->currentPage(),
                    'last_page' => $jobs->lastPage(),
                    'from' => $jobs->firstItem(),
                    'to' => $jobs->lastItem(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch job applications',
                'error' => $e->getMessage()
            ], 500);
        }
    }
/**
 * Update the specified job application.
 *
 * @param  \Illuminate\Http\Request  $request
 * @param  int  $id
 * @return \Illuminate\Http\JsonResponse
 */
   public function updateAppliedJob(Request $request, $id)
   {
    try {
        $validated = $request->validate([
            'cv_created' => 'sometimes|boolean',
            'interview_practice' => 'sometimes|boolean',
            'applied' => 'sometimes|boolean',
            'status' => 'sometimes|in:prep,appSent,shortListed,1stInterview,2ndInterview,finalInterview,onHold,OfferAcctepted,UnSuccessful'
        ]);

        GettingStartedStep::where('user_id', auth()->id())
        ->update(['progress_tracker' => true]);

        $application = JobApplication::where('user_id', Auth::id())
            ->findOrFail($id);

        // Only update the fields that are present in the request
        $updatableFields = [
            'cv_created',
            'interview_practice',
            'applied',
            'status'
        ];

        foreach ($updatableFields as $field) {
            if ($request->has($field)) {
                $application->$field = $validated[$field];
            }
        }

        $application->save();

        return response()->json([
            'success' => true,
            'data' => $application,
            'message' => 'Job application updated successfully'
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validation error',
            'errors' => $e->errors()
        ], 422);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Job application not found'
        ], 404);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to update job application',
            'error' => $e->getMessage()
        ], 500);
    }
   }

   public function deleteAppliedJob($id)
   {
    try {
        $application = JobApplication::where('user_id', Auth::id())
            ->findOrFail($id);

        $application->delete();

        return response()->json([
            'success' => true,
            'message' => 'Job application deleted successfully'
        ]);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Job application not found'
        ], 404);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to delete job application',
            'error' => $e->getMessage()
        ], 500);
    }
   }
}
