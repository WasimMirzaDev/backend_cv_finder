<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PdfParsed;
use App\Models\CvResume;
use App\Models\CvRecentActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;
use thiagoalessio\TesseractOCR\TesseractOCR;

class ResumeController extends Controller
{
    private $affindaApiKey = 'aff_291fc3fb665dd0870c3246f2399e196f0f4ea3a3';
    private $affindaBaseUrl = 'https://api.affinda.com/v3';
    private $workspace = 'dTFpVCey';
    private $documentType = 'yzGpvUYM';

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function createEmpty(Request $request)
    {
        $request->validate([
            'newEmptyResume' => 'required',
        ]);

        $newEmptyResume = $request->newEmptyResume;

        // Create a new resume
        $resume = CvResume::create([
            'user_id' => auth()->id(),
            'title' => 'My Resume',
            'cv_resumejson' => $newEmptyResume,
        ]);
        
        // Log activity
        CvRecentActivity::create([
            'user_id' => auth()->id(),
            'type' => 'resume',
            'type_id' => $resume->id,
            'message' => 'Created a new resume!',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $resume
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'cv_resumejson' => 'required',
        ]);

        $cv_resumejson = $request->cv_resumejson;

        $resume = CvResume::findOrFail($id);
        $resume->cv_resumejson = $cv_resumejson;
        $resume->save();

        // Log activity
        CvRecentActivity::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'type' => 'resume',
                'type_id' => $resume->id,
            ],
            [
            'message' => 'Worked on a resume!',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $resume
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Upload and parse a resume using Affinda API
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */

    //  public function parseResume(Request $request)
    //  {
    //      $request->validate([
    //          'file' => 'required|mimes:pdf|max:20480'
    //      ]);
     
    //      $file = $request->file('file');
    //      $path = storage_path('app/temp/' . $file->getClientOriginalName());
    //      $file->move(storage_path('app/temp'), $file->getClientOriginalName());
     
    //      $command = "python scripts/layoutlmv3_parser.py " . escapeshellarg($path);
    //      $output = shell_exec($command);
     
    //      // Log the raw output
    //      \Log::info("Python raw output: " . $output);
     
    //      if (!$output) {
    //          return response()->json([
    //              'success' => false,
    //              'message' => 'Python script returned nothing or failed to run.'
    //          ], 500);
    //      }
     
    //      $cleanOutput = mb_convert_encoding($output, 'UTF-8', 'UTF-8');
     
    //      $decoded = json_decode($cleanOutput, true);
     
    //      if (json_last_error() !== JSON_ERROR_NONE) {
    //          return response()->json([
    //              'success' => false,
    //              'message' => 'Invalid JSON returned by script',
    //              'error' => json_last_error_msg(),
    //              'raw_output' => $output
    //          ], 500);
    //      }
     
    //      return response()->json([
    //          'success' => true,
    //          'data' => $decoded
    //      ]);
    //  }

    public function staticFileRunner(){
        try {
            // Define the path to the demo CV file
            $filePath = public_path('demo_cv/CV_DEMO.pdf');
            
            // Log the file path for debugging
            \Log::info('Attempting to process file: ' . $filePath);
            
            // Check if file exists
            if (!file_exists($filePath)) {
                \Log::error('File not found: ' . $filePath);
                return response()->json([
                    'success' => false,
                    'error' => 'Demo CV file not found at: ' . $filePath,
                    'file_exists' => file_exists($filePath),
                    'directory' => dirname($filePath),
                    'files_in_directory' => file_exists(dirname($filePath)) ? scandir(dirname($filePath)) : []
                ], 404);
            }
            
            // Define the Python command
            $pythonPath = '/var/www/html/backend_cv_finder/env/bin/python';
            $scriptPath = public_path('scripts/parse_resume.py');
            
            // Check if Python script exists
            if (!file_exists($scriptPath)) {
                \Log::error('Python script not found: ' . $scriptPath);
                return response()->json([
                    'success' => false,
                    'error' => 'Python script not found',
                    'script_path' => $scriptPath,
                    'script_exists' => file_exists($scriptPath)
                ], 500);
            }
            
            // Build and execute the command
            $command = sprintf(
                '%s %s "%s"',
                escapeshellarg($pythonPath),
                escapeshellarg($scriptPath),
                str_replace('"', '\"', $filePath)
            );
            
            \Log::info('Executing command: ' . $command);
            
            // Execute the command and capture output and return code
            $output = [];
            $returnVar = 0;
            exec($command . ' 2>&1', $output, $returnVar);
            $output = implode("\n", $output);
            
            // Log the raw output for debugging
            \Log::info('Python script output:', ['output' => $output]);
            
            // Handle command execution errors
            if ($returnVar !== 0) {
                throw new \RuntimeException("Python script execution failed with code: $returnVar");
            }
            
            // Try to decode the JSON output
            $decodedOutput = json_decode($output, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException("Failed to decode JSON output: " . json_last_error_msg());
            }
            
            return response()->json([
                'success' => true,
                'data' => $decodedOutput,
                'debug' => [
                    'file_path' => $filePath,
                    'file_exists' => file_exists($filePath),
                    'command' => $command,
                    'return_code' => $returnVar
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error in staticFileRunner: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'exception' => [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ],
                'debug' => [
                    'file_path' => $filePath ?? 'not set',
                    'file_exists' => isset($filePath) ? file_exists($filePath) : 'file path not set',
                    'last_output' => $output ?? 'no output captured'
                ]
            ], 500);
        }
    }


     public function parseResumeOCRPyScript(Request $request)
     {
         $request->validate([
             'file' => 'required|mimes:pdf|max:20480'
         ]);
         $model = $request->model ?? 'gpt-4o-mini';
         $file = $request->file('file');
         $path = storage_path('app/temp/' . $file->getClientOriginalName());
         $file->move(storage_path('app/temp'), $file->getClientOriginalName());
         
         $command = escapeshellcmd("/var/www/html/backend_cv_finder/env/bin/python scripts/parse_resume.py \"$path\"");
         $output = shell_exec($command);

         dd($output);
         // Ensure clean UTF-8 output
         $output = trim($output);
         $cleanOutput = mb_convert_encoding($output, 'UTF-8', 'UTF-8');
     
         $decoded = json_decode($cleanOutput, true);
     
        //  if (json_last_error() !== JSON_ERROR_NONE || !isset($decoded['data']['raw_text'])) {
        //      return response()->json([
        //          'success' => false,
        //          'message' => 'Invalid JSON or malformed UTF-8 from Python',
        //          'raw_output' => $cleanOutput
        //      ], 500);
        //  }
     
         // Ensure raw_text is valid UTF-8 for response
        //  $rawText = mb_convert_encoding($decoded['data']['raw_text'], 'UTF-8', 'UTF-8');
     
        //  return response()->json([
        //      'success' => true,
        //      'data' => $rawText
        //  ], 200, [], JSON_UNESCAPED_UNICODE);
         

         try {
            $apiKey = config('services.openai.api_key');
        
            // Construct detailed evaluation prompt based on the framework
            $prompt = <<<PROMPT
You are a resume parsing AI. Analyze the candidate's CV and extract structured information in the following JSON format. Fill as many fields as possible based on the text.

### RAW TEXT:
"{$cleanOutput}"

### REQUIRED JSON FORMAT:
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
"type": "Specialized Skill"
}
]
}
}

Rules:
- Respond ONLY with JSON — no extra commentary.
- Leave fields as `null` if the value is unknown or not found.
- Ensure `rawText` contains the same original content provided.
PROMPT;

            $gptResponse = Http::timeout(60)->withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model, // Dynamic model selection
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a professional JSON resume parser. Respond ONLY with valid full JSON.'
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

            // return $parsedData;

            if ($gptResponse->json()) {
                $pdfParsed = new PdfParsed();
                $pdfParsed->ip_address = $request->ip();
                $pdfParsed->user_agent = $request->userAgent();
                if (isset($parsedData['data']['candidateName'][0]['firstName'], $parsedData['data']['candidateName'][0]['familyName'])) {
                    $pdfParsed->full_name = $parsedData['data']['candidateName'][0]['firstName'] . ' ' . $parsedData['data']['candidateName'][0]['familyName'];
                }
                $pdfParsed->file_name = $file->getClientOriginalName();
                $pdfParsed->parsed_data = $parsedData;
                $pdfParsed->save();
                return response()->json([
                    'success' => true,
                    'data' => $parsedData
                ]);
            }

            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to get evaluation from AI model', 'details' => $e->getMessage()], 500);
        }
     
         return response()->json([
             'success' => true,
             'data' => $decoded
         ]);
     }
     




    public function parseResumeGPT(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:204800', // ~200MB
        ]);
        $model = $request->input('model', 'gpt-4o-mini'); 
        $file = $request->file('file');
        $text = '';
        $tempImagePath = null;
    
        try {
            // 1. First try direct text extraction
            $parser = new Parser();
            $pdf = $parser->parseFile($file->getRealPath());
            $text = trim($pdf->getText());
    
            // 2. Fallback to OCR if text extraction fails
            if (strlen($text) < 100) {
                $outputBase = public_path('ocr/pdf_to_image');
                
                // Create directory if it doesn't exist
                if (!file_exists($outputBase)) {
                    mkdir($outputBase, 0755, true);
                }
    
                $filenameBase = uniqid('page_');
                $imagePathPrefix = "$outputBase/{$filenameBase}";
    
                // Convert first page to image
                $command = sprintf(
                    'pdftoppm -jpeg -f 1 -l 1 "%s" "%s"',
                    escapeshellarg($file->getRealPath()),
                    escapeshellarg($imagePathPrefix)
                );
                
                exec($command, $output, $returnCode);
    
                if ($returnCode !== 0) {
                    throw new \Exception('Failed to convert PDF to image');
                }
    
                $firstImage = $imagePathPrefix . '-1.jpg';
                if (!file_exists($firstImage)) {
                    throw new \Exception('Could not generate image for OCR');
                }
    
                $tempImagePath = $firstImage;
                $text = (new TesseractOCR($firstImage))->run();
    
                // Store the original PDF in public folder as well if needed
                $pdfPath = $outputBase . '/' . uniqid('resume_') . '.pdf';
                move_uploaded_file($file->getRealPath(), $pdfPath);
            }else{
                try {
                    $apiKey = config('services.openai.api_key');
                
                    // Construct detailed evaluation prompt based on the framework
                    $prompt = <<<PROMPT
You are a resume parsing AI. Analyze the candidate's CV and extract structured information in the following JSON format. Fill as many fields as possible based on the text.

### RAW TEXT:
"{$text}"

### REQUIRED JSON FORMAT:
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
        "type": "Specialized Skill"
      }
    ]
  }
}

Rules:
- Respond ONLY with JSON — no extra commentary.
- Leave fields as `null` if the value is unknown or not found.
- Ensure `rawText` contains the same original content provided.
PROMPT;
        
                    $gptResponse = Http::timeout(60)->withHeaders([
                        'Authorization' => "Bearer {$apiKey}",
                        'Content-Type' => 'application/json',
                    ])->post('https://api.openai.com/v1/chat/completions', [
                        'model' => $model, // Dynamic model selection
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => 'You are a professional JSON resume parser. Respond ONLY with valid full JSON.'
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

                    // return $parsedData;

                    if ($gptResponse->json()) {
                        $pdfParsed = new PdfParsed();
                        $pdfParsed->ip_address = $request->ip();
                        $pdfParsed->user_agent = $request->userAgent();
                        if (isset($parsedData['data']['candidateName'][0]['firstName'], $parsedData['data']['candidateName'][0]['familyName'])) {
                            $pdfParsed->full_name = $parsedData['data']['candidateName'][0]['firstName'] . ' ' . $parsedData['data']['candidateName'][0]['familyName'];
                        }
                        $pdfParsed->file_name = $file->getClientOriginalName();
                        $pdfParsed->parsed_data = $parsedData;
                        $pdfParsed->save();
                        return response()->json([
                            'success' => true,
                            'data' => $parsedData
                        ]);
                    }

                    
                } catch (\Exception $e) {
                    return response()->json(['error' => 'Failed to get evaluation from AI model', 'details' => $e->getMessage()], 500);
                }
            }


            return response()->json([
                'success' => true,
                'text' => $text,
                'file_name' => $file->getClientOriginalName(),
                // Add the public URL if you want to access the generated files
                'image_url' => isset($firstImage) ? asset(str_replace(public_path(), '', $firstImage)) : null,
                'pdf_url' => isset($pdfPath) ? asset(str_replace(public_path(), '', $pdfPath)) : null,
            ]);
    
        } catch (\Exception $e) {
            // Clean up temp files
            if ($tempImagePath && file_exists($tempImagePath)) {
                @unlink($tempImagePath);
            }
    
            \Log::error('Resume parsing failed: ' . $e->getMessage(), [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getTraceAsString()
            ]);
    
            return response()->json([
                'success' => false,
                'message' => 'Failed to process resume: ' . $e->getMessage()
            ], 500);
        }
    }




    // public function parseResume(Request $request)
    // {
    //     $request->validate([
    //         'file' => 'required|file|mimes:pdf,doc,docx|max:204800',  // Max 200MB
    //     ]);

    //     try {
    //         $file = $request->file('file');

    //         // Create a temporary file with a unique name
    //         $tempFile = tmpfile();
    //         $tempFilePath = stream_get_meta_data($tempFile)['uri'];

    //         // Copy the uploaded file to the temporary file
    //         file_put_contents($tempFilePath, file_get_contents($file->getRealPath()));

    //         // Prepare the request to Affinda API
    //         $response = Http::withHeaders([
    //             'Authorization' => 'Bearer ' . $this->affindaApiKey,
    //         ])
    //             ->attach(
    //                 'file',
    //                 file_get_contents($tempFilePath),
    //                 $file->getClientOriginalName(),
    //                 ['Content-Type' => $file->getMimeType()]
    //             )
    //             ->post($this->affindaBaseUrl . '/documents', [
    //                 'wait' => 'true',
    //                 'workspace' => $this->workspace,
    //                 'documentType' => $this->documentType,
    //                 'compact' => 'true',
    //             ]);

    //         // Close and remove the temporary file
    //         fclose($tempFile);

    //         if ($response->successful()) {
    //             $parsedData = $response->json();
    //             $pdfParsed = new PdfParsed();
    //             $pdfParsed->ip_address = $request->ip();
    //             $pdfParsed->user_agent = $request->userAgent();
    //             if (isset($parsedData['data']['candidateName'][0]['firstName'], $parsedData['data']['candidateName'][0]['familyName'])) {
    //                 $pdfParsed->full_name = $parsedData['data']['candidateName'][0]['firstName'] . ' ' . $parsedData['data']['candidateName'][0]['familyName'];
    //             }
    //             $pdfParsed->file_name = $file->getClientOriginalName();
    //             $pdfParsed->parsed_data = $parsedData;
    //             $pdfParsed->save();
    //             return response()->json([
    //                 'success' => true,
    //                 'data' => $response->json()
    //             ]);
    //         }

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to parse resume',
    //             'error' => $response->json()
    //         ], $response->status());
    //     } catch (\Exception $e) {
    //         // Clean up temp file if it exists
    //         if (isset($filePath) && Storage::disk('local')->exists($filePath)) {
    //             Storage::disk('local')->delete($filePath);
    //         }

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'An error occurred while processing your request',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }
    
}
