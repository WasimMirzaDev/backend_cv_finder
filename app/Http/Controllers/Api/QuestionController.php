<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\DifficultyLevel;
use App\Models\QuestionType;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class QuestionController extends Controller
{
    public function FeedQuestionData(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls'
        ]);

        try {
            $import = new class implements ToModel, WithHeadingRow, WithBatchInserts, WithChunkReading {
                public $imported = 0;
                public $skipped = 0;
                public $errors = [];

                public function model(array $row)
                {
                    // Skip empty rows
                    if (empty(array_filter($row))) {
                        $this->skipped++;
                        return null;
                    }

                    // Validate required columns
                    if (!isset($row['unique_id'], $row['speech'], $row['title'])) {
                        $this->skipped++;
                        $this->errors[] = 'Missing required columns in a row';
                        return null;
                    }

                    $uniqueId = $row['unique_id'];

                    // Parse unique_id (e.g., 'EINDT1')
                    if (!preg_match('/^([A-Z])([A-Z]{3})([A-Za-z]{1,2})(\d+)$/', $uniqueId, $matches)) {
                        $this->skipped++;
                        $this->errors[] = "Invalid unique_id format: " . $uniqueId;
                        return null;
                    }

                    list(, $difficultySlug, $questionTypeSlug, $subcategorySlug, $questionNumber) = $matches;

                    // Find or create difficulty level
                    $difficulty = DifficultyLevel::firstOrCreate(
                        ['slug' => $difficultySlug],
                        ['name' => $this->getDifficultyName($difficultySlug)]
                    );

                    // Find or create question type
                    $questionType = QuestionType::firstOrCreate(
                        ['slug' => $questionTypeSlug],
                        ['name' => $this->getQuestionTypeName($questionTypeSlug)]
                    );

                    // Find or create subcategory
                    $subcategory = Subcategory::firstOrCreate(
                        [
                            'questiontype_id' => $questionType->id,
                            'slug' => $subcategorySlug
                        ],
                        ['name' => $this->getSubcategoryName($subcategorySlug, $questionTypeSlug)]
                    );

                    $this->imported++;

                    return new Question([
                        'speech' => $row['speech'],
                        'title' => $row['title'],
                        'unique_id' => $uniqueId,
                        'avatar' => $row['avatar'] ?? null,
                        'video_id' => $row['video_id'] ?? null,
                        'difficulty_slug' => $difficulty->slug,
                        'questiontype_slug' => $questionType->slug,
                        'subcategories_slug' => $subcategory->slug,
                        'question_number' => (int)$questionNumber,
                    ]);
                }

                public function batchSize(): int
                {
                    return 1000;
                }

                public function chunkSize(): int
                {
                    return 1000;
                }

                private function getDifficultyName($slug)
                {
                    $names = [
                        'E' => 'Easy',
                        'M' => 'Medium',
                        'H' => 'Hard'
                    ];

                    return $names[$slug] ?? ucfirst(strtolower($slug));
                }

                private function getQuestionTypeName($slug)
                {
                    $names = [
                        'IND' => 'Industry',
                        'BUS' => 'Business Sector',
                        'BEH' => 'Behavioural',
                        'GTK' => 'Get to Know'
                    ];

                    return $names[$slug] ?? ucfirst(strtolower($slug));
                }

                private function getSubcategoryName($slug, $questionTypeSlug)
                {
                    // You can expand this based on your subcategories
                    return ucfirst(strtolower($slug));
                }
            };

            // Import the data
            Excel::import($import, $request->file('file'));

            return response()->json([
                'message' => 'Data imported successfully',
                'imported' => $import->imported,
                'skipped' => $import->skipped,
                'errors' => $import->errors
            ]);

        } catch (\Exception $e) {
            Log::error('Error importing questions: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Error importing data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    public function getQuestions(Request $request)
    {
        // Check if any of the required parameters are missing
        if (empty($request->difficulty_slug) || empty($request->questiontype_slug) || empty($request->subcategories_slug)) {
            // Return first 6 questions if any parameter is missing
            $questions = Question::take(6)->get();
        } else {
            // All parameters are present, filter by all conditions
            $questions = Question::where('difficulty_slug', $request->difficulty_slug)
                ->where('questiontype_slug', $request->questiontype_slug)
                ->where('subcategories_slug', $request->subcategories_slug)
                ->get();
        }
        
        return response()->json($questions);
    }
    
    public function getQuestion($questionId)
    {
        $question = Question::where('id', $questionId)->first();
       
        $industry = QuestionType::where('slug', $question->questiontype_slug)->first();
        $business_sector = Subcategory::where('slug', $question->subcategories_slug)->where('questiontype_id', $industry->id)->first();
        return response()->json([
            'question' => $question,
            'industry' => $industry,
            'business_sector' => $business_sector
        ]);
    }
}
