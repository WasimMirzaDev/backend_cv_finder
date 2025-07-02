<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PdfParsed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
    public function store(Request $request)
    {
        //
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
        //
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
    public function parseResume(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx|max:204800',  // Max 200MB
        ]);

        try {
            $file = $request->file('file');

            // Create a temporary file with a unique name
            $tempFile = tmpfile();
            $tempFilePath = stream_get_meta_data($tempFile)['uri'];

            // Copy the uploaded file to the temporary file
            file_put_contents($tempFilePath, file_get_contents($file->getRealPath()));

            // Prepare the request to Affinda API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->affindaApiKey,
            ])
                ->attach(
                    'file',
                    file_get_contents($tempFilePath),
                    $file->getClientOriginalName(),
                    ['Content-Type' => $file->getMimeType()]
                )
                ->post($this->affindaBaseUrl . '/documents', [
                    'wait' => 'true',
                    'workspace' => $this->workspace,
                    'documentType' => $this->documentType,
                    'compact' => 'true',
                ]);

            // Close and remove the temporary file
            fclose($tempFile);

            if ($response->successful()) {
                $parsedData = $response->json();
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
                    'data' => $response->json()
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to parse resume',
                'error' => $response->json()
            ], $response->status());
        } catch (\Exception $e) {
            // Clean up temp file if it exists
            if (isset($filePath) && Storage::disk('local')->exists($filePath)) {
                Storage::disk('local')->delete($filePath);
            }

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your request',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
}
