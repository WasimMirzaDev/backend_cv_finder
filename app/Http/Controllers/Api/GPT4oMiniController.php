<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class GPT4oMiniController extends Controller
{
    public function generateCvAi(Request $request)
    {
        $request->validate([
            'jobTitle' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
        ]);

        $apiKey = config('services.openai.api_key');

        $name = Auth::user()->name;
        $email = Auth::user()->email;

        $prompt = <<<EOT
            Create a professional, ATS-optimized CV in JSON format using the provided user details. Ensure it's written in a clear, natural tone with proper localization. Include all required fields, even if null. Generate relevant content from the description when possible. Return only valid JSON without any explanations or markdown.

            User data:
            Name: {$name}
            Email: {$email}
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
            $response = Http::timeout(60)->withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
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
                'response_format' => ['type' => 'json_object'], // Ensure JSON output
                'max_tokens' => 3000 , // Allow for detailed evaluation
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



    public function generateCoverLetterAi(Request $request){
      $request->validate([
        'jsonResume' => 'required',
      ]);

    $apiKey = config('services.openai.api_key');

    // $name = Auth::user()->name;
    // $email = Auth::user()->email;

    $prompt = <<<EOT
    Create a professional, ATS-optimized Cover Letter in JSON format using the provided user details. 
    Ensure it's written in a clear, natural tone with proper localization. 
    Include all required fields, even if null. 
    Generate relevant content from the description, qualifications, and employment history when possible. 
    Return only valid JSON without any explanations or markdown.
    
    User data: {$request->jsonResume}
    
    Here is the required format you must follow (fill with relevant professional text, never placeholders, use null if no data):
    
    {
      "header": {
        "applicant_name": "John Doe",
        "applicant_address": "123 Main Street, Faisalabad, Pakistan",
        "applicant_email": "johndoe@email.com",
        "applicant_phone": "+92 300 1234567",
        "date": "2025-09-05"
      },
      "recipient": {
        "hiring_manager_name": "Jane Smith",
        "company_name": "Tech Solutions Ltd.",
        "company_address": "456 Business Road, London, UK"
      },
      "body": {
        "greeting": "Dear Hiring Manager,",
        "opening_paragraph": "I am writing to express my interest in the Frontend Developer position at Tech Solutions Ltd. With a strong background in React.js, Next.js, and Laravel, I bring both technical expertise and problem-solving skills to the role.",
        "middle_paragraphs": [
          "During my previous role as a Laravel Developer at Techtrack Software Solutions, I worked on developing scalable web applications, implementing RESTful APIs, and integrating third-party services.",
          "I have also gained experience in Flutter and Dart for mobile development, and I am currently enhancing my skills in Machine Learning to contribute to data-driven applications."
        ],
        "closing_paragraph": "I would welcome the opportunity to discuss how my skills and enthusiasm can contribute to your team’s success. Thank you for considering my application.",
        "signature": "Sincerely, John Doe"
      }
    }
    EOT;
    

    try {
        $response = Http::timeout(60)->withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a Cover Letter JSON generator. Always reply in strict JSON matching the required format with all keys present. Use null if no value is suitable.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.0,
            'response_format' => ['type' => 'json_object'], // Ensure JSON output
            'max_tokens' => 3000 , // Allow for detailed evaluation
        ]);

        $content = $response->json();

        if (isset($content['choices'][0]['message']['content'])) {
            $aiText = $content['choices'][0]['message']['content'];

            // Extract only the JSON part
            $jsonStart = strpos($aiText, '{');
            if ($jsonStart !== false) {
                $jsonString = substr($aiText, $jsonStart);
                $decoded = json_decode($jsonString, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    return response()->json($decoded);
                }

                return response()->json([
                    'error' => 'Invalid JSON from GPT-4o-mini',
                    'raw' => $aiText,
                ], 500);
            }

            return response()->json([
                'error' => 'No JSON found in GPT-4o-mini response',
                'raw' => $aiText,
            ], 500);
        }

        return response()->json([
            'error' => 'Empty response from GPT-4o-mini',
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
