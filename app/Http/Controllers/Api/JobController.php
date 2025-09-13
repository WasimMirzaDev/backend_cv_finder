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
                'date_posted' => $request->input('date_posted', 'week'),
            ]);

           $data = $response->json();
           return $data;
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
    
                $gptResponse = Http::timeout(60)->withHeaders([
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
}
