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
use Barryvdh\DomPDF\Facade\Pdf;
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
    if($resume->user_id != Auth::user()->id) {
        // Return paginated response for unauthorized delete
        $perPage = request()->per_page ?? 3;
        $page = request()->page ?? 1;
        
        $query = CvRecentActivity::where('user_id', Auth::user()->id)
            ->where('type', 'resume')
            ->with(['resume', 'interview']);
        
        $total = $query->count();
        $recentActivities = $query->latest()
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get()
            ->map(function ($activity) {
                $activity->unsetRelation($activity->type === 'interview' ? 'resume' : 'interview');
                return $activity;
            });
            
        return response()->json([
            'success' => false,
            'message' => 'Cannot delete this resume.',
            'data' => [
                'data' => $recentActivities,
                'total' => $total,
                'per_page' => (int)$perPage,
                'current_page' => (int)$page,
                'last_page' => ceil($total / $perPage)
            ]
        ], 403);
    }

    // Delete the resume
    $resume->delete();

    // Delete the related activity
    CvRecentActivity::where('user_id', Auth::user()->id)
        ->where('type_id', $id)
        ->where('type', 'resume')
        ->delete();

    // Return paginated recent activities
    $perPage = request()->per_page ?? 5;
    $page = request()->page ?? 1;
    
    $query = CvRecentActivity::where('user_id', Auth::user()->id)
        ->where('type', 'resume')
        ->with(['resume', 'interview']);
    
    $total = $query->count();
    $recentActivities = $query->latest()
        ->skip(($page - 1) * $perPage)
        ->take($perPage)
        ->get()
        ->map(function ($activity) {
            $activity->unsetRelation($activity->type === 'interview' ? 'resume' : 'interview');
            return $activity;
        });

    return response()->json([
        'success' => true,
        'message' => 'Resume deleted successfully',
        'data' => [
            'data' => $recentActivities,
            'total' => $total,
            'per_page' => (int)$perPage,
            'current_page' => (int)$page,
            'last_page' => ceil($total / $perPage)
        ]
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

    /**
 * Helper method to extract text from any element
 */
private function extractTextFromElement($element)
{
    $text = '';
    
    if (method_exists($element, 'getElements')) {
        foreach ($element->getElements() as $child) {
            $text .= $this->extractTextFromElement($child);
        }
    } elseif (method_exists($element, 'getText')) {
        $text .= $element->getText();
    }
    
    return $text;
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
            $Systemprompt = <<<PROMPT
                ROLE
                - You read the raw CV text from the user.
                - You analyze the contents and elaborate / expand where this may be lacking
                - You output ONE valid JSON object conforming to the SCHEMA below.
                - You enrich the CV to be recruiter-friendly and evidence-anchored without inventing data.
                
                CORE RULES
                1) Output JSON only — no extra text.
                2) Preserve all stated facts (names, dates, metrics, employers).
                3) Never invent new numbers, employers, degrees, or certifications.
                4) If >40% of a summary or bullet is generalized wording, set "confidence":"inferred"; otherwise "stated".
                5) Use {$style_adjective} language style.---
                6) UK spelling and date formats (e.g., Mar 2023 – Jul 2025).
                7) Consistent tense and formatting.
                
                SUMMARY
                - Produce ONE cohesive paragraph (80–130 words).
                - Use strong verbs (Led, Built, Designed, Delivered, Optimized).
                - Cover, where relevant: technical/domain scope, scalability/performance, collaboration/leadership, quality/security/UX.
                - Reuse stated metrics verbatim (e.g. “improved performance by 30%”).
                - No fabricated metrics.

                WorkExperience
                - workExperienceDescription Should be atleast (100–150 words).
                

                BULLET DECOMPOSITION (REINFORCED)
                - Every experience entry MUST have *3–7 bullets*. Fewer than 3 is INVALID.
                - If duties appear as a single sentence, *DECOMPOSE* into discrete bullets that each cover one facet:
                  (a) what was built/delivered,
                  (b) integrations/security,
                  (c) performance/scalability (reuse stated metrics),
                  (d) collaboration/leadership/delivery,
                  (e) quality/testing/reliability,
                  (f) architecture/tooling.
                - Each bullet is *one concise ATS-friendly sentence* and starts with a strong verb.
                - Avoid combining multiple facets into one bullet.
                - For generic expansions, set "confidence":"inferred".
                
                BULLET DECOMPOSITION EXAMPLES
                SOURCE:
                "Developed REST APIs, integrated third-party services, and managed databases with focus on optimisation and scalability."
                TARGET:
                - Designed and developed RESTful APIs powering customer-facing web and mobile applications. (inferred)
                - Integrated third-party services with secure, reliable data exchange and webhook handling. (inferred)
                - Optimized queries and caching to improve average API response time by ~35% where measured. (stated if present)
                - Implemented asynchronous jobs and queues to maintain responsiveness under heavy load. (inferred)
                - Collaborated with product and QA to deliver production-ready features on predictable timelines. (inferred)
                                
                **Parse the CV**  
                Analyze the candidate's CV and extract structured information in the following JSON format. Fill as many fields as possible based on the text.
                
                
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
                "summary": {
                    "paragraph": "",
                    "years_experience": null,
                    "confidence": "stated"
                },
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
                "highlights": {
                "minItems": 3,
                "maxItems": 7,
                "items":  [{
                    "bullet": "",
                    "impact": "",
                    "keywords": "",
                    "confidence": ""
                  },
                  ],
                },
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
                
                PROMPT;


                // **Transform into ATS-friendly UK CV**  
                // After parsing, transform the CV into a tailored, ATS-friendly UK CV text aligned with the provided style adjective and, if available, the job description.  
                
                // Strict ATS formatting rules:
                // - Plain text only (no tables, columns, text boxes, graphics, emojis, or icons).
                // - Standard headings only: Candidate Headline, Profile, Key Skills, Experience, Education, Certifications (if present), Projects (if present), Additional (if present).
                // - Reverse-chronological order.
                // - Bullet points for responsibilities/achievements.
                // - UK spelling and date formats (e.g., Mar 2023 – Jul 2025).
                // - Consistent tense and formatting.
                
                // Style & quality requirements:
                // - Add a Candidate Headline (up to 8 words) directly beneath the candidate’s name and contact details.
                // - Summarise profession, specialism, and/or career focus.
                // - Align with the {$style_adjective} style and (if available) {$job_description}.
                // - Optimise for ATS keyword matching.
                // - Reflect the {$style_adjective} style throughout.
                // - Use active voice, strong verbs, and quantify achievements where possible.
                // - Preserve factual details (names, dates, employers). Do not invent.
                // - If JD provided, emphasise relevant experience/skills and insert [Placeholder: …] for missing requirements.
                // - Do not insert optional sections if missing in source.
                
                // Output:
                // - First, return the JSON structure.
              
                
                // - Then, provide the final ATS CV text.

            $gptResponse = Http::timeout(180)->withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model, // Dynamic model selection
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $Systemprompt
                    ],
                    [
                        'role' => 'user',
                        'content' => "Raw Text : {$cleanOutput}"
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
                'file' => 'required|file|mimes:pdf|max:204800', 
            ]);
            
            $model = $request->input('model', 'gpt-4o-mini'); 
            $file = $request->file('file');
            
            // Create training data directories
            $trainingBase = public_path('training_data');
            $pdfDir = $trainingBase . '/resume-pdfs';
            $jsonDir = $trainingBase . '/resume-jsons';
            $ocrDir = $trainingBase . '/ocr-outputs';
            
            // Ensure directories exist
            if (!file_exists($pdfDir)) mkdir($pdfDir, 0755, true);
            if (!file_exists($jsonDir)) mkdir($jsonDir, 0755, true);
            if (!file_exists($ocrDir)) mkdir($ocrDir, 0755, true);
            
            try {
                // Generate unique filename with timestamp
                $timestamp = time();
                $uniqueId = uniqid();
                $baseFilename = "resume_{$timestamp}_{$uniqueId}";
                
                // Store original PDF
                $pdfFilename = $baseFilename . '.pdf';
                $pdfPath = $pdfDir . '/' . $pdfFilename;
                $file->move($pdfDir, $pdfFilename);
                
                $text = '';
                $usedOcr = false;
                $ocrPath = null;
                
                // 1. First try direct text extraction
                $parser = new Parser();
                $pdf = $parser->parseFile($pdfPath);
                $text = trim($pdf->getText());
                
                // 2. Fallback to OCR if text extraction fails
                if (strlen($text) < 100) {
                    $usedOcr = true;
                    
                    // Use Python script for OCR
                    $pythonPath = '/var/www/html/backend_cv_finder/env/bin/python';
                    $scriptPath = public_path('scripts/parse_resume.py');
                    $command = sprintf(
                        '%s %s "%s"',
                        escapeshellarg($pythonPath),
                        escapeshellarg($scriptPath),
                        str_replace('"', '\"', $pdfPath)
                    );
                    
                    $output = shell_exec($command . ' 2>&1');
                    $cleanOutput = mb_convert_encoding(trim($output), 'UTF-8', 'UTF-8');
                    
                    // Save OCR output to file
                    $ocrFilename = $baseFilename . '_ocr.txt';
                    $ocrPath = $ocrDir . '/' . $ocrFilename;
                    file_put_contents($ocrPath, $cleanOutput);
                    
                    $text = $cleanOutput;
                }
                
                // Process with GPT regardless of OCR or direct extraction
                $apiKey = config('services.openai.api_key');
                
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
                    'model' => $model,
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
                    'temperature' => 0.0,
                    'response_format' => ['type' => 'json_object'],
                    'max_tokens' => 2048,
                ]);

                $evaluation = $gptResponse->json()['choices'][0]['message']['content'] ?? null;
                $parsedData = json_decode($evaluation, true);

                if ($evaluation) {
                    $aiText = $evaluation;
                    
                    // Extract only the JSON part
                    $jsonStart = strpos($aiText, '{');
                    if ($jsonStart !== false) {
                        $jsonString = substr($aiText, $jsonStart);
                        $decoded = json_decode($jsonString, true);
                        
                        if (json_last_error() === JSON_ERROR_NONE && isset($decoded['data'])) {
                            $parsedData = $decoded;
                        }
                    }
                }

                // Save JSON to training data
                $jsonFilename = $baseFilename . '.json';
                $jsonPath = $jsonDir . '/' . $jsonFilename;
                file_put_contents($jsonPath, json_encode($parsedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                // Save to database
                $pdfParsed = new PdfParsed();
                $pdfParsed->ip_address = $request->ip();
                $pdfParsed->user_agent = $request->userAgent();
                if (isset($parsedData['data']['candidateName'][0]['firstName'], $parsedData['data']['candidateName'][0]['familyName'])) {
                    $pdfParsed->full_name = $parsedData['data']['candidateName'][0]['firstName'] . ' ' . $parsedData['data']['candidateName'][0]['familyName'];
                }
                $pdfParsed->file_name = $file->getClientOriginalName();
                $pdfParsed->parsed_data = $parsedData;
                $pdfParsed->training_file_id = $baseFilename; // Store the unique ID for reference
                $pdfParsed->used_ocr = $usedOcr;
                $pdfParsed->save();

                return response()->json([
                    'success' => true,
                    'data' => $parsedData,
                    'training_data' => [
                        'file_id' => $baseFilename,
                        'pdf_path' => asset(str_replace(public_path(), '', $pdfPath)),
                        'json_path' => asset(str_replace(public_path(), '', $jsonPath)),
                        'ocr_used' => $usedOcr,
                        'ocr_path' => $usedOcr ? asset(str_replace(public_path(), '', $ocrPath)) : null,
                        'text_length' => strlen($text)
                    ]
                ]);

            } catch (\Exception $e) {
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


    public function download($id, Request $request)
    {
        try {
            // 1. Get resume record from DB
            $resume = CvResume::findOrFail($id);

            if(!$resume){
                return response()->json([
                    'success' => false,
                    'message' => 'Resume not found'
                ], 404);
            }
            
            // 2. Validate template parameter
            $template = $request->input('template');
            if (!$template) {
                return response()->json([
                    'success' => false,
                    'message' => 'Template parameter is required'
                ], 400);
            }
            $template = strtolower($template);
            
            // Validate template exists
            $validTemplates = ['classic', 'default', 'luxe', 'modern'];
            if (!in_array($template, $validTemplates)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid template. Valid templates: ' . implode(', ', $validTemplates)
                ], 400);
            }

            // 3. Get and decode resume data
            $resumeData = $resume->cv_resumejson;
            
            // Check if cv_resumejson is null or empty
            if (empty($resumeData)) {
                \Log::error('Resume data is empty', ['resume_id' => $id]);
                return response()->json([
                    'success' => false,
                    'message' => 'Resume data is empty'
                ], 400);
            }
            
            // If cv_resumejson is a JSON string, decode it
            if (is_string($resumeData)) {
                $decoded = json_decode($resumeData, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    \Log::error('Failed to decode resume JSON', [
                        'resume_id' => $id,
                        'error' => json_last_error_msg()
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid resume data format: ' . json_last_error_msg()
                    ], 400);
                }
                $resumeData = $decoded;
            }
            
            // Log resume data structure for debugging
            \Log::info('Resume data structure', [
                'resume_id' => $id,
                'data_keys' => is_array($resumeData) ? array_keys($resumeData) : 'not an array',
                'data_type' => gettype($resumeData)
            ]);
            
            // Validate that resumeData is an array
            if (!is_array($resumeData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resume data is not in the correct format'
                ], 400);
            }

            // 4. Pass it to Blade template
            $pdf = Pdf::loadView($template.'-template', compact('resumeData'))
                      ->setPaper('a4', 'portrait');

            // 5. Download as PDF
            $filename = ($resumeData['candidateName'][0]['firstName'] ?? 'CV') . '.pdf';
            return $pdf->download($filename);
            
        } catch (\Exception $e) {
            \Log::error('Error generating PDF', [
                'resume_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error generating PDF: ' . $e->getMessage()
            ], 500);
        }
    }
}
