<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Models\Question;
use App\Models\User;
use App\Models\Interview;
use App\Models\QuestionType;
use App\Models\Subcategory;
use App\Models\CvRecentActivity;
use Illuminate\Support\Facades\Auth;
use App\Models\GettingStartedStep;

class InterviewController extends Controller
{
    // public function submitAudio(Request $request)
    // {
    //     $questionId = $request->input('question_id');
    //     $audio = $request->file('audio');
    //     $question = Question::find($questionId);
    //     $industry = QuestionType::where('slug', $question->questiontype_slug)->first();
    //     $business_sector = Subcategory::where('slug', $question->subcategories_slug)->where('questiontype_id', $industry->id)->first();

    //     if (!$audio) {
    //         return response()->json(['error' => 'No audio file provided'], 400);
    //     }

    //     // Ensure directory exists
    //     $directory = public_path('interview/audio');
    //     if (!file_exists($directory)) {
    //         mkdir($directory, 0777, true);
    //     }

    //     // Generate unique filename
    //     $filename = uniqid() . '.' . $audio->getClientOriginalExtension();
    //     $audioPath = 'interview/audio/' . $filename;
    //     $fullPath = $directory . '/' . $filename;

    //     // Move audio file
    //     $audio->move($directory, $filename);

    //     // Send audio to Whisper for transcription
    //     try {
    //         $response = Http::withToken(config('services.gpt4o-mini.api_key'))
    //             ->attach(
    //                 'file',
    //                 fopen($fullPath, 'r'),
    //                 $filename
    //             )
    //             ->post('https://api.openai.com/v1/audio/transcriptions', [
    //                 'model' => 'whisper-1',
    //             ]);

    //         $text = $response['text'] ?? 'No transcription returned';

    //         try {
    //             $apiKey = config('services.gpt4o-mini.api_key');
            
    //             // 🧠 Construct prompt
    //             $prompt = "
    //         Act as an AI interview coach. Evaluate the following response to an interview question.
            
    //         QUESTION:
    //         \"{$question->speech}\"
            
    //         CANDIDATE'S RESPONSE:
    //         \"{$text}\"
            
    //         Return a JSON object in this format:
            
    //         {
    //           \"score\": number (0-100),
    //           \"issues\": [{\"title\": string, \"description\": string}, ...],
    //           \"ideal_response\": \"string\"
    //         }
            
    //         Strictly respond ONLY with valid JSON.
    //         ";
            
    //             $gptResponse = Http::withHeaders([
    //                 'Authorization' => "Bearer {$apiKey}",
    //                 'Content-Type' => 'application/json',
    //             ])->post('https://api.openai.com/v1/chat/completions', [
    //                 'model' => 'gpt-4o-mini',
    //                 'messages' => [
    //                     [
    //                         'role' => 'system',
    //                         'content' => 'You are an AI interviewer. Always respond with valid JSON following the given structure.'
    //                     ],
    //                     [
    //                         'role' => 'user',
    //                         'content' => $prompt
    //                     ]
    //                 ],
    //                 'temperature' => 0.0,
    //                 'max_tokens' => 1024,
    //             ]);
            
    //             $evaluation = $gptResponse->json()['choices'][0]['message']['content'] ?? null;
            
    //             // Optionally decode JSON if needed
    //             $parsed = json_decode($evaluation, true);
            
    //         } catch (\Exception $e) {
    //             return response()->json(['error' => 'Failed to get evaluation from GPT-4o', 'details' => $e->getMessage()], 500);
    //         }
            
    //     } catch (\Exception $e) {
    //         return response()->json(['error' => 'Failed to transcribe audio', 'details' => $e->getMessage()], 500);
    //     }

    //     $interview = Interview::create([
    //         'user_id' => Auth::user()->id,
    //         'question_id' => $questionId,
    //         'audio_path' => $audioPath,
    //         'transcription' => $text,
    //         'evaluation' => $parsed,
    //     ]);

    //     CvRecentActivity::create([
    //         'user_id' => Auth::user()->id,
    //         'type' => 'interview',
    //         'type_id' => $interview->id,
    //         'message' => 'Submitted an interview.! ' . $question->speech,
    //         'ip_address' => request()->ip(),
    //         'user_agent' => request()->userAgent(),
    //     ]);

    //     return response()->json([
    //         'message' => 'Audio submitted & transcribed successfully',
    //         'transcription' => $text,
    //         'audio_path' => $audioPath,
    //         'evaluation' => $parsed,
    //         'question' => $question,
    //         'industry' => $industry,
    //         'business_sector' => $business_sector
    //     ]);
    // }


    public function submitAudio(Request $request)
{
    $questionId = $request->input('question_id');
    $audio = $request->file('audio');
    $model = $request->input('model', 'gpt-4o-mini'); // Default to gpt-4 if not specified
    $question = Question::find($questionId);
    $industry = QuestionType::where('slug', $question->questiontype_slug)->first();
    $business_sector = Subcategory::where('slug', $question->subcategories_slug)->where('questiontype_id', $industry->id)->first();

    if (!$audio) {
        return response()->json(['error' => 'No audio file provided'], 400);
    }

    // Ensure directory exists
    $directory = public_path('interview/audio');
    if (!file_exists($directory)) {
        mkdir($directory, 0777, true);
    }

    // Generate unique filename
    $filename = uniqid() . '.' . $audio->getClientOriginalExtension();
    $audioPath = 'interview/audio/' . $filename;
    $fullPath = $directory . '/' . $filename;

    // Move audio file
    $audio->move($directory, $filename);

    // Send audio to Whisper for transcription
    try {
        $response = Http::timeout(120)->withToken(config('services.openai.api_key'))
            ->attach(
                'file',
                fopen($fullPath, 'r'),
                $filename
            )
            ->post('https://api.openai.com/v1/audio/transcriptions', [
                'model' => 'whisper-1',
            ]);

        $text = $response['text'] ?? 'No transcription returned';

        try {
            $apiKey = config('services.openai.api_key');
        
            // Construct detailed evaluation prompt based on the framework
            $prompt = <<<PROMPT
        Act as an AI interview assessment system. Strictly evaluate the candidate's response using the following framework:
        
        ### QUESTION:
        "{$question->speech}"
        
        ### CANDIDATE'S RESPONSE:
        "{$text}"
        
        ### EVALUATION FRAMEWORK:
        
        **CATEGORY 1: STAR Method Assessment (50% weight)**
        1. SITUATION (Max 9/10):
           - 9: Clear time/place/role, concise setup
           - 7: Missing some details
           - 5: Vague (e.g., "a project once")
           - 0: No context
           Rules: Deduct for irrelevant details. Unclear relevance = max 5.
        
        2. TASK (Max 9/10):
           - 9: Specific goal with personal ownership
           - 7: Unclear or lacks ownership
           - 5: Team-only focus
           - 0: No task
           Rules: Team tasks without 'I' = max 5. Off-topic = max 3.
        
        3. ACTION (Max 18/20):
           - 18: 'I' statements with clear steps/rationale
           - 14: Some vagueness but mostly 'I'
           - 10: Passive/team-focused ("we did")
           - 0: No action
           Rules: Team-only = below 14. No rationale = max 10.
        
        4. RESULT (Max 9/10):
           - 9: Measurable outcome + reflection
           - 7: Linked to task but vague
           - 5: Generic ("it went well")
           - 0: No result
           Rules: Team results = max 5. No reflection = max 7.
        
        **CATEGORY 2: Delivery & Communication (20% weight)**
        1. CLARITY (Max 7/8):
           - 7: Logical STAR flow, no repetition
           - 5: Some meandering
           - 0: Disorganized
           Rules: Deduct for repetition/tangents.
        
        2. FILLER WORDS (Max 3/4):
           - 3: <3 fillers per 100 words
           - 1: >6 fillers per 100 words
           - 0: Excessive fillers
           Rules: Track: "um", "like", "you know".
        
        3. PROFESSIONAL LANGUAGE (Max 3/4):
           - 3: Formal tone
           - 1: Casual ("stuff", "kind of")
           - 0: Slang/inappropriate
        
        4. CONFIDENCE (Max 3/4):
           - 3: Active voice ("I decided")
           - 1: Hedging ("maybe", "I think")
           - 0: Passive/self-doubting
        
        **CATEGORY 3: Advanced Competencies (30% weight)**
        1. CRITICAL THINKING (Max 7/8):
           - 7: Rationale + reflection ("I learned...")
           - 3: Actions only, no 'why'
           - 0: No reasoning
        
        2. TEAMWORK BALANCE (Max 4/5):
           - 4: Balances 'I' and 'we'
           - 2: Overuses 'we'
           - 0: No personal role
           Rules: Only 'we' = max 2.
        
        3. GOAL ORIENTATION (Max 4/5):
           - 4: Specific KPI ("reduced time by 20%")
           - 2: Vague goal
           - 0: No goal
        
        4. EMOTIONAL INTELLIGENCE (Max 5/6):
           - 5: Shows empathy/adaptation ("I listened...")
           - 2: Mentions others but no EQ
           - 0: No interpersonal awareness
        
        5. STRUCTURAL COHERENCE (Max 2/3):
           - 2: Clear intro/middle/end
           - 0: Disordered
        
        6. QUESTION RELEVANCE (Max 2/3):
           - 2: Directly answers question
           - 0: Off-topic
        
        **GLOBAL RULES:**
        - Never exceed 97% total score
        - Prioritize measurable results over vague claims
        - Penalize excessive team-focus without 'I' ownership
        - Deduct points for vague language
        - Highlight measurable results over vague claims
        
        **OUTPUT FORMAT (strict JSON):**
        {
          "breakdown": {
            "star_method": {
              "situation": {score: number, feedback: string},
              "task": {score: number, feedback: string},
              "action": {score: number, feedback: string},
              "result": {score: number, feedback: string},
              "total": {score: number, feedback: string}
            },
            "communication": {
              "clarity": {score: number, feedback: string},
              "filler_words": {score: number, feedback: string},
              "professional_language": {score: number, feedback: string},
              "confidence": {score: number, feedback: string},
              "total": {score: number, feedback: string}
            },
            "competencies": {
              "critical_thinking": {score: number, feedback: string},
              "teamwork_balance": {score: number, feedback: string},
              "goal_orientation": {score: number, feedback: string},
              "emotional_intelligence": {score: number, feedback: string},
              "structural_coherence": {score: number, feedback: string},
              "question_relevance": {score: number, feedback: string},
              "total": {score: number, feedback: string}
            },
            "total": {score: number, feedback: string}
          },
          "top_improvements": [{"title": string, "description": string}, ...],
          "ideal_response": "string"
        }
        
        Respond ONLY with valid JSON.
        PROMPT;
        
            $gptResponse = Http::timeout(120)->withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model, // Dynamic model selection
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an AI interviewer. Strictly follow the evaluation framework and respond ONLY with valid JSON.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.0, // Minimize randomness
                'response_format' => ['type' => 'json_object'], // Ensure JSON output
                'max_tokens' => 1500, // Allow for detailed evaluation
            ]);
        
            $evaluation = $gptResponse->json()['choices'][0]['message']['content'] ?? null;
            $parsed = json_decode($evaluation, true);
            
            // Validate scoring doesn't exceed 97%
            if (isset($parsed['breakdown']['total']['score']) && $parsed['breakdown']['total']['score'] > 97) {
                $parsed['breakdown']['total']['score'] = 97;
            }
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to get evaluation from AI model', 'details' => $e->getMessage()], 500);
        }
        
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to transcribe audio', 'details' => $e->getMessage()], 500);
    }

    $interview = Interview::create([
        'user_id' => Auth::user()->id,
        'question_id' => $questionId,
        'audio_path' => $audioPath,
        'transcription' => $text,
        'evaluation' => $parsed,
        'model_used' => $model, // Store which model was used
    ]);

    CvRecentActivity::create([
        'user_id' => Auth::user()->id,
        'type' => 'interview',
        'type_id' => $interview->id,
        'message' => 'Submitted an interview: ' . $question->speech,
        'ip_address' => request()->ip(),
        'user_agent' => request()->userAgent(),
    ]);

    
    GettingStartedStep::where('user_id', auth()->id())
    ->update(['first_interview' => true]);

    return response()->json([
        'message' => 'Audio submitted & evaluated successfully',
        'transcription' => $text,
        'audio_path' => $audioPath,
        'evaluation' => $parsed,
        'question' => $question,
        'industry' => $industry,
        'business_sector' => $business_sector,
        'model_used' => $model
    ]);
}


    public function getInterviewHistory(Request $request)
    {
        $searchTerm = $request->searchTerm;
        if($searchTerm && $searchTerm != ""){
            $interviews = Interview::where('user_id', Auth::user()->id)->with('question')->whereHas('question', function ($query) use ($searchTerm) {
                $query->where('speech', 'like', "%{$searchTerm}%");
                $query->where('title', 'like', "%{$searchTerm}%");
            })->latest()->get()->take($request->limit??3);
        }else{
            $interviews = Interview::where('user_id', Auth::user()->id)->with('question')->latest()->get()->take($request->limit??3);
        }
        return response()->json($interviews);
    }
}
