<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DifficultyLevel;
use App\Models\QuestionType;
use App\Models\Subcategory;
use Illuminate\Http\Request;

class QuestionFilterController extends Controller
{
    /**
     * Get all difficulty levels
     */
    public function getDifficulties()
    {
        $difficulties = DifficultyLevel::select('id', 'name', 'slug')
            ->orderBy('id')
            ->get();
            
        return response()->json($difficulties);
    }

    /**
     * Get all question types (industries)
     */
    public function getQuestionTypes()
    {
        $questionTypes = QuestionType::select('id', 'name', 'slug')
            ->orderBy('id')
            ->get();
            
        return response()->json($questionTypes);
    }

    public function editQuestionType(Request $request, $id)
    {
        $questionType = QuestionType::findOrFail($id);
        $questionType->name = $request->name;
        $questionType->save();
        return response()->json($questionType);
    }

    /**
     * Get subcategories by question type
     */
    public function getSubcategories($questionTypeId)
    {
        $subcategories = Subcategory::where('questiontype_id', $questionTypeId)
            ->select('id', 'name', 'slug')
            ->orderBy('name')
            ->get();
            
        return response()->json($subcategories);
    }

    public function getAllSubcategories()
    {
        $subcategories = Subcategory::select('id', 'name', 'slug')
            ->orderBy('name')
            ->get();
            
        return response()->json($subcategories);
    }

    public function editSubcategory(Request $request, $id)
    {
        $subcategory = Subcategory::findOrFail($id);
        $subcategory->name = $request->name;
        $subcategory->save();
        return response()->json($subcategory);
    }

    public function editDifficultyLevel(Request $request, $id)
    {
        $difficultyLevel = DifficultyLevel::findOrFail($id);
        $difficultyLevel->name = $request->name;
        $difficultyLevel->save();
        return response()->json($difficultyLevel);
    }
}
