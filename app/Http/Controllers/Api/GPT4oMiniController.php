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


      // return $request->jsonResume;

      $apiKey = config('services.openai.api_key');

      // Accept either JSON string or already-parsed array/object
      $resumePayload = $request->jsonResume;
      if (is_string($resumePayload)) {
        $decoded = json_decode($resumePayload, true);
        if (json_last_error() === JSON_ERROR_NONE) {
          $resumePayload = $decoded;
        }
      }

      // Ensure we pass a compact JSON for the model context
      $resumeJson = json_encode($resumePayload, JSON_UNESCAPED_UNICODE);

      // Use today's date as a sane default if model cannot infer a date
      $today = now()->format('F j, Y');

      $instructions = <<<EOT
You generate ATS-friendly cover letters as strict JSON only.
Rules:
- Parse the provided resume JSON and extract real values for name, address, email, and phone.
- Use resume content to write tailored paragraphs highlighting relevant achievements, skills, and experience.
- If any field is missing in the resume, set it to null except "date" which should default to {$today}.
- Do not invent company details unless present in input; keep them null.
- Always return a single JSON object matching the schema below. No markdown or commentary.

Schema:
{
  "header": {
    "applicant_name": string|null,
    "applicant_address": string|null,
    "applicant_email": string|null,
    "applicant_phone": string|null,
    "date": string
  },
  "recipient": {
    "hiring_manager_name": string|null,
    "company_name": string|null,
    "company_address": string|null
  },
  "body": {
    "greeting": string,
    "opening_paragraph": string,
    "middle_paragraphs": [string, ...],
    "closing_paragraph": string,
    "signature": string
  }
}

Extraction hints (use best-effort mapping):
- Name: data.candidateName[0].firstName + " " + data.candidateName[0].familyName OR data.name OR basics.name
- Email: data.email[0] OR data.basics.email
- Phone: data.phoneNumber[0].formattedNumber OR data.phone OR basics.phone
- Address: data.location.formatted OR join([street, city, stateCode, postalCode, country]) when present OR basics.location.address
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
              'content' => $instructions
            ],
            [
              'role' => 'user',
              'content' => "RESUME_JSON:" . $resumeJson
            ],
          ],
          'temperature' => 0.1,
          'response_format' => ['type' => 'json_object'],
          'max_tokens' => 2000,
        ]);

        $content = $response->json();

        if (!isset($content['choices'][0]['message']['content'])) {
          return response()->json([
            'error' => 'Empty response from GPT-4o-mini',
            'raw' => $content,
          ], 500);
        }

        $jsonText = $content['choices'][0]['message']['content'];
        $decoded = json_decode($jsonText, true);
        if (json_last_error() === JSON_ERROR_NONE) {
          return response()->json($decoded);
        }

        return response()->json([
          'error' => 'Invalid JSON from GPT-4o-mini',
          'raw' => $jsonText,
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
