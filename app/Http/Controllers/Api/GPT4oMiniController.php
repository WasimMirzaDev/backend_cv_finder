<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Http;

class GPT4oMiniController extends Controller
{
    public function generateCvAi(Request $request)
    {
        $request->validate([
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'phone' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'jobTitle' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
        ]);

        $apiKey = config('services.openai.api_key');

        $prompt = <<<EOT
            You are a professional CV writer who adapts to the candidate’s local language and grammar based on the user input. 
            Ensure the CV is:
            1. Written in a clear, natural, human tone;
            2. Applicant Tracking System (ATS) optimised (no tables or graphics);
            3. Aligned with the tone and keywords from the user input and job description if available;
            4. Written using the local spelling, grammar, date formats, and job titles inferred from the candidate’s input (e.g., UK vs US English).

            Using the user data provided below, respond ONLY with a complete JSON structure matching the following format. Your response must start with `{` and must contain all keys from the sample, even if the values are null. Do not explain anything. Do not include markdown or code blocks.and use you AI skills to implement data from description to other keys and try to fill most of values if it is reasonable and modify data if it is not reasonable and write summary based on description.

            User data:
            First Name: {$request->firstName}
            Last Name: {$request->lastName}
            Email: {$request->email}
            Phone: {$request->phone}
            Address: {$request->address}
            Job Title: {$request->jobTitle}
            Description: {$request->description}

            Here is the required format you must follow:

            {
              "data": {
                "candidateName": [
                  {
                    "firstName": "",
                    "familyName": ""
                  }
                ],
                "headline": "",
                "website": null,
                "preferredWorkLocation": null,
                "willingToRelocate": null,
                "objective": null,
                "association": null,
                "hobby": null,
                "patent": null,
                "publication": null,
                "referee": null,
                "dateOfBirth": null,
                "headshot": null,
                "nationality": null,
                "email": [""],
                "phoneNumber": [
                  {
                    "rawText": "",
                    "countryCode": "",
                    "nationalNumber": "",
                    "formattedNumber": "",
                    "internationalCountryCode": ""
                  }
                ],
                "location": {
                  "city": "",
                  "state": "",
                  "poBox": null,
                  "street": null,
                  "country": "",
                  "latitude": null,
                  "formatted": "",
                  "longitude": null,
                  "rawInput": "",
                  "stateCode": "",
                  "postalCode": null,
                  "countryCode": "",
                  "streetNumber": null,
                  "apartmentNumber": null
                },
                "availability": null,
                "summary": "",
                "expectedSalary": null,
                "education": [
                  {
                    "educationAccreditation": "",
                    "educationOrganization": "",
                    "educationDates": {
                      "end": {
                        "day": null,
                        "date": "",
                        "year": null,
                        "month": null,
                        "isCurrent": false
                      },
                      "start": {
                        "day": null,
                        "date": "",
                        "year": null,
                        "month": null,
                        "isCurrent": false
                      },
                      "durationInMonths": null
                    },
                    "educationMajor": [],
                    "educationLevel": {
                      "id": null,
                      "label": "",
                      "value": ""
                    }
                  }
                ],
                "workExperience": [
                  {
                    "workExperienceJobTitle": "",
                    "workExperienceOrganization": "",
                    "workExperienceDates": {
                      "end": {
                        "day": null,
                        "date": "",
                        "year": null,
                        "month": null,
                        "isCurrent": true
                      },
                      "start": {
                        "day": null,
                        "date": "",
                        "year": null,
                        "month": null,
                        "isCurrent": false
                      },
                      "durationInMonths": null
                    },
                    "workExperienceDescription": "",
                    "workExperienceType": {
                      "id": null,
                      "label": "",
                      "value": ""
                    }
                  }
                ],
                "totalYearsExperience": null,
                "project": null,
                "achievement": null,
                "rightToWork": null,
                "languages": [
                  {
                    "name": "",
                    "level": null
                  }
                ],
                "skill": [
                  {
                    "name": "",
                    "type": "Specialized Skill",
                  },
                ],
                "rawText": "Faraz Mehmood Contact
            Passionate web developer with expertise in creating scalable,....."
              }
            }
            EOT;

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a CV JSON generator. Always reply in strict JSON matching the required format with all keys present. Use null if no value is suitable.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.0,
                'max_tokens' => 2048,
            ]);

            $content = $response->json();

            if (isset($content['choices'][0]['message']['content'])) {
                $aiText = $content['choices'][0]['message']['content'];

                // Extract only the JSON part
                $jsonStart = strpos($aiText, '{');
                if ($jsonStart !== false) {
                    $jsonString = substr($aiText, $jsonStart);
                    $decoded = json_decode($jsonString, true);

                    if (json_last_error() === JSON_ERROR_NONE && isset($decoded['data'])) {
                        return response()->json($decoded);
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

            return response()->json([
                'error' => 'Empty response from GPT-4o',
                'raw' => $content
            ], 500);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Exception thrown',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function analyzeResume(Request $request)
    {
        $request->validate([
            'paragraph' => 'required|string|max:1000',
        ]);

        $paragraph = $request->input('paragraph');
        $apiKey = config('services.openai.api_key');

        try {
            // Single API call to get both analysis and suggestions
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a professional CV/resume analyzer. Analyze the provided CV paragraph and provide both issues and suggested improvements.'
                    ],
                    [
                        'role' => 'user',
                        'content' => "Analyze this CV profile paragraph:\n\n{$paragraph}\n\n" .
                                     "Respond with a JSON object containing:\n" .
                                     "1. 'issues': An array of exactly 5 objects, each with 'issue' and 'description' (max 15 words per description)\n" .
                                     "2. 'suggested_paragraph': A rewritten version of the paragraph that is more impactful, specific with technologies, achievements, and role focus. Keep it concise.\n\n" .
                                     "Format your response as a valid JSON object with these exact keys: {\"issues\": [{\"issue\": string, \"description\": string}, ...], \"suggested_paragraph\": string}"
                    ]
                ],
                'temperature' => 0.5,
                'max_tokens' => 1024,
                'response_format' => ['type' => 'json_object']
            ]);

            $content = $response->json();

            if (!isset($content['choices'][0]['message']['content'])) {
                return response()->json([
                    'status' => false,
                    'error' => 'Empty response from GPT-4o',
                    'raw' => $content
                ], 500);
            }


            $aiResponse = json_decode($content['choices'][0]['message']['content'], true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'status' => false,
                    'error' => 'Invalid JSON from GPT-4o',
                    'raw' => $content['choices'][0]['message']['content']
                ], 500);
            }

            return response()->json([
                'status' => true,
                'data' => [
                    'issues' => $aiResponse['issues'] ?? [],
                    'suggested_changes' => $aiResponse['suggested_paragraph'] ?? '',
                    'original_paragraph' => $paragraph
                ]
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'error' => 'Exception occurred',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}
