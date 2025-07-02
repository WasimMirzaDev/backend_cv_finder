<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GeminiController extends Controller
{
    public function analyzeResume(Request $request)
    {
        $request->validate([
            'paragraph' => 'required|string|max:1000',
        ]);

        $paragraph = $request->input('paragraph');
        $apiKey = config('services.gemini.api_key');

        try {
            // First API call to get issues
            $responseIssues = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => "Analyze this CV profile paragraph and identify exactly 5 main issues in json encoded format. For each issue, provide a very short description (max 15 words). Be extremely concise and direct. Here's the paragraph: {$paragraph} . dont return anything except json use keys issue and description"
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.5,  // Lower temperature for more focused responses
                    'maxOutputTokens' => 200,
                ]
            ]);

            if (!$responseIssues->successful()) {
                return response()->json([
                    'status' => false,
                    'error' => 'API response error',
                    'details' => $responseIssues->body(),
                ], $responseIssues->status());
            }

            $issuesData = $responseIssues->json();
            $rawIssues = $issuesData['candidates'][0]['content']['parts'][0]['text'] ?? 'No content generated.';

            // Process issues into a clean array
            $issues = $rawIssues;

            // Second API call to get suggested changes
            $responseChanges = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => "Rewrite this CV profile paragraph to be more impactful. Make it specific with technologies, achievements, and role focus. Keep it concise. Original paragraph: {$paragraph} . dont return anything except json use key revised_profile"
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.5,
                    'maxOutputTokens' => 150,
                ]
            ]);

            if (!$responseChanges->successful()) {
                return response()->json([
                    'status' => false,
                    'error' => 'API response error',
                    'details' => $responseChanges->body(),
                ], $responseChanges->status());
            }

            $changesData = $responseChanges->json();
            $suggestedParagraph = $changesData['candidates'][0]['content']['parts'][0]['text'] ?? 'No content generated.';

            // Clean up the suggested paragraph
            // $suggestedParagraph = preg_replace('/^"|"$/', '', trim($suggestedParagraph));
            // $suggestedParagraph = str_replace('\n', ' ', $suggestedParagraph);

            return response()->json([
                'status' => true,
                'data' => [
                    'issues' => $issues,  // Ensure exactly 5 issues
                    'suggested_changes' => $suggestedParagraph,
                    'original_paragraph' => $paragraph
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'error' => 'Exception occurred',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
