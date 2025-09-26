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
use Illuminate\Support\Facades\Auth;
use App\Models\GettingStartedStep;

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

        GettingStartedStep::where('user_id', auth()->id())
        ->update(['first_cv' => true]);
    

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
        
        $resume = CvResume::findOrFail($id);
        if($resume->user_id == Auth::user()->id){
            return response()->json([
                'success' => true,
                'data' => $resume
            ]);
        }else{
            return response()->json([
                'success' => false,
                'message' => "Required CV Not Found!"
            ]);
        }
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
    public function delete(string $id)
    {
        $resume = CvResume::findOrFail($id);
        if($resume->user_id == Auth::user()->id){
        $resume->delete();
        }
        else{
            $recentActivities = CvRecentActivity::where('user_id', Auth::user()->id)
            ->where('type','resume')
            ->with(['resume', 'interview'])
            ->latest()
            ->take($request->limit ?? 3)
            ->get()
            ->map(function ($activity) {
                $activity->unsetRelation($activity->type === 'interview' ? 'resume' : 'interview');
                return $activity;
            });
            return response()->json([
                'success' => true,
                'message' => 'Cannot delete this resume.',
                'data' => $recentActivities
            ],403);
        }

        $recentActivity = CvRecentActivity::where('user_id', Auth::user()->id)
        ->where('type_id', $id)
        ->where('type','resume')
        ->with(['resume', 'interview'])
        ->first();

        $recentActivity->delete();


        $recentActivities = CvRecentActivity::where('user_id', Auth::user()->id)
        ->where('type','resume')
        ->with(['resume', 'interview'])
        ->latest()
        ->take($request->limit ?? 3)
        ->get()
        ->map(function ($activity) {
            $activity->unsetRelation($activity->type === 'interview' ? 'resume' : 'interview');
            return $activity;
        });


        return response()->json([
            'success' => true,
            'message' => 'Resume deleted successfully',
            'data' => $recentActivities
        ]);
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
            'file' => 'required|mimes:pdf,png,jpg,jpeg,docx'
        ]);
        $model = $request->model ?? 'gpt-4o-mini';
        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $path = storage_path('app/temp/' . $originalName);
        $file->move(storage_path('app/temp'), $originalName);

        $cleanOutput = '';

        if ($extension == 'docx') {
            // Handle DOCX files using PhpOffice\PhpWord
            try {
                $phpWord = \PhpOffice\PhpWord\IOFactory::load($path);
                $text = '';
                
                foreach ($phpWord->getSections() as $section) {
                    $elements = $section->getElements();
                    
                    foreach ($elements as $element) {
                        if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
                            // Handle TextRun elements
                            foreach ($element->getElements() as $textElement) {
                                if ($textElement instanceof \PhpOffice\PhpWord\Element\Text) {
                                    $text .= $textElement->getText();
                                }
                            }
                            $text .= "\n"; // Add newline after each TextRun
                        } elseif ($element instanceof \PhpOffice\PhpWord\Element\Text) {
                            // Handle direct Text elements
                            $text .= $element->getText() . "\n";
                        } elseif ($element instanceof \PhpOffice\PhpWord\Element\Table) {
                            // Handle tables
                            foreach ($element->getRows() as $row) {
                                foreach ($row->getCells() as $cell) {
                                    $text .= $this->extractTextFromElement($cell) . "\t";
                                }
                                $text .= "\n";
                            }
                        }
                    }
                }
                
                $cleanOutput = mb_convert_encoding(trim($text), 'UTF-8', 'UTF-8');
                return response()->json(['success' => true, 'data' => $cleanOutput]);
                
            } catch (\Exception $e) {
                \Log::error('Error processing DOCX file', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'file' => $path
                ]);
                return response()->json([
                    'error' => 'Failed to process DOCX file',
                    'details' => $e->getMessage()
                ], 500);
            }
        } else {
            // Handle PDF and images with Python script
            $pythonPath = '/var/www/html/backend_cv_finder/env/bin/python';
            $scriptPath = public_path('scripts/parse_resume.py');
            $command = sprintf(
                '%s %s "%s"',
                escapeshellarg($pythonPath),
                escapeshellarg($scriptPath),
                str_replace('"', '\"', $path)
            );

            $output = shell_exec($command . ' 2>&1');
            $cleanOutput = mb_convert_encoding(trim($output), 'UTF-8', 'UTF-8');
        }

        try {
            $apiKey = config('services.openai.api_key');
            $style_adjective = $request->languageStyle ?? "Friendly";
            $job_description = $request->additionalInfo ?? "";          
        
            // Construct detailed evaluation prompt based on the framework
            $prompt = <<<PROMPT
                You are an expert UK CV writer, ATS specialist, and resume parsing AI.
                
                You will receive:
                - A candidate’s CV (raw text).
                - A style adjective (e.g., Professional, Creative, Analytical, Friendly, Results-Driven, Strategic, Technical, Collaborative, Entrepreneurial).
                - (Optional) A job description (JD).
                
                Your tasks are:
                
                1. **Parse the CV**  
                Analyze the candidate's CV and extract structured information in the following JSON format. Fill as many fields as possible based on the text.
                

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
                "achievement": [],
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

                ---

                2. **Transform into ATS-friendly UK CV**  
                After parsing, transform the CV into a tailored, ATS-friendly UK CV text aligned with the provided style adjective and, if available, the job description.  
                
                Strict ATS formatting rules:
                - Plain text only (no tables, columns, text boxes, graphics, emojis, or icons).
                - Standard headings only: Candidate Headline, Profile, Key Skills, Experience, Education, Certifications (if present), Projects (if present), Additional (if present).
                - Reverse-chronological order.
                - Bullet points for responsibilities/achievements.
                - UK spelling and date formats (e.g., Mar 2023 – Jul 2025).
                - Consistent tense and formatting.
                
                Style & quality requirements:
                - Add a Candidate Headline (up to 8 words) directly beneath the candidate’s name and contact details.
                - Summarise profession, specialism, and/or career focus.
                - Align with the {$style_adjective} style and (if available) {$job_description}.
                - Optimise for ATS keyword matching.
                - Reflect the {$style_adjective} style throughout.
                - Use active voice, strong verbs, and quantify achievements where possible.
                - Preserve factual details (names, dates, employers). Do not invent.
                - If JD provided, emphasise relevant experience/skills and insert [Placeholder: …] for missing requirements.
                - Do not insert optional sections if missing in source.
                
                Output:
                - First, return the JSON structure.
                PROMPT;
                // - Then, provide the final ATS CV text.

            $gptResponse = Http::timeout(120)->withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model, // Dynamic model selection
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert UK CV writer and employability coach. Always use UK English grammar and 
spelling. Produce output that is ATS-friendly for UK recruitment.Only return valid json.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.0, // Minimize randomness
                'response_format' => ['type' => 'json_object'], // Ensure JSON output
                'max_tokens' => 5000, // Allow for detailed evaluation
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
                        $pdfParsed = new PdfParsed();
                        $pdfParsed->ip_address = $request->ip();
                        $pdfParsed->user_agent = $request->userAgent();
                        if (isset($decoded['data']['candidateName'][0]['firstName'], $decoded['data']['candidateName'][0]['familyName'])) {
                            $pdfParsed->full_name = $decoded['data']['candidateName'][0]['firstName'] . ' ' . $decoded['data']['candidateName'][0]['familyName'];
                        }
                        $pdfParsed->file_name = $originalName;
                        $decoded['data']['languageStyle'] = $style_adjective;
                        $pdfParsed->parsed_data = $decoded;
                        $pdfParsed->save();

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
                'error' => 'Failed to get evaluation from AI model',
            ], 500);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to get evaluation from AI model', 'details' => $e->getMessage()], 500);
        }
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
