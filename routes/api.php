<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\GPT4oMiniController;
use App\Http\Controllers\Api\ResumeController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\QuestionFilterController;
use App\Http\Controllers\Api\InterviewController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CvRecentActivityController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\PlanController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RevolutController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\Gateways\StripeWebhookController;
use App\Http\Controllers\Api\Gateways\StripeController;

/*
 * |--------------------------------------------------------------------------
 * | API Routes
 * |--------------------------------------------------------------------------
 * |
 * | Here is where you can register API routes for your application. These
 * | routes are loaded by the RouteServiceProvider within a group which
 * | is assigned the "api" middleware group. Enjoy building your API!
 * |
 */

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/upload-profile', [UserController::class, 'uploadProfile']);

    //interview related
    Route::post('/v1/resume/create-empty', [ResumeController::class, 'createEmpty']);
    Route::get('/v1/resume-by-id/{id}', [ResumeController::class, 'show']);
    Route::put('/v1/resume/{id}', [ResumeController::class, 'update']);
    Route::get('/interview/history', [InterviewController::class, 'getInterviewHistory']);
    Route::post('/interview/submit-audio', [InterviewController::class, 'submitAudio']);

    //recent acitivities
    Route::get('/recent-activities', [CvRecentActivityController::class, 'index']);

    Route::get('/fetch-jobs', [JobController::class, 'fetchJobs']);


    Route::get('/stripe/create-subscription-session/{planId}', [StripeController::class, 'createSubscriptionSession']);
    Route::get('/subscription/details', [StripeController::class, 'getSubscriptionDetails']);
    Route::post('/subscription/cancel', [StripeController::class, 'cancelSubscription']);
    Route::get('/subscription/payment-method', [StripeController::class, 'getPaymentMethod']);
    Route::delete('/subscription/payment-method/{id}', [StripeController::class, 'deletePaymentMethod']);
    Route::get('/subscription/payment-method-intent/{customerId}', [StripeController::class, 'createSetupIntent']);
    Route::post('/subscription/payment-method-default/{customerId}', [StripeController::class, 'makeDefaultPaymentMethod']);
    Route::post('/subscription/change-plan/{planId}', [StripeController::class, 'changePlan']);


    //cv related
    Route::post('/generate-cv-ai', [GPT4oMiniController::class, 'generateCvAi']);
    Route::post('/generate-cover-letter', [GPT4oMiniController::class, 'generateCoverLetterAi'])
});


// Example API route
Route::get('/test', function () {
    return response()->json(['message' => 'API is working!']);
});

// Resume parsing endpoint
Route::post('/parse-resume', [ResumeController::class, 'parseResumeOCRPyScript']);
Route::post('/analyze-paragraph', [GPT4oMiniController::class, 'analyzeResume']);
Route::get('/migrate', function () {
    \Illuminate\Support\Facades\Artisan::call('migrate');
    return 'Migrate Complete!';
});
Route::post('/feed-question-data', [QuestionController::class, 'FeedQuestionData']);

// Filter endpoints
Route::prefix('filters')->group(function () {
    Route::get('/difficulties', [QuestionFilterController::class, 'getDifficulties']);
    Route::get('/question-types', [QuestionFilterController::class, 'getQuestionTypes']);
    Route::get('/question-types/{questionTypeId}/subcategories', [QuestionFilterController::class, 'getSubcategories']);
    Route::get('/subcategories', [QuestionFilterController::class, 'getAllSubcategories']);
    Route::put('/subcategories/{subcategoryId}', [QuestionFilterController::class, 'editSubcategory']);
    Route::put('/question-types/{questionTypeId}', [QuestionFilterController::class, 'editQuestionType']);
    Route::put('/difficulties/{difficultyId}', [QuestionFilterController::class, 'editDifficultyLevel']);
});

Route::get('/questions', [QuestionController::class, 'getQuestions']);
Route::get('/questions/{questionId}', [QuestionController::class, 'getQuestion']);

Route::get('/get-industries', [UserController::class, 'getIndustries']);
Route::get('/get-roles', [UserController::class, 'getRoles']);
Route::get('/get-education-levels', [UserController::class, 'getEducationLevels']);

Route::apiResource('plans', PlanController::class);

Route::get('/revolut/accounts', [RevolutController::class, 'accounts']);
Route::post('/revolut/payout-link', [RevolutController::class, 'createPayoutLink']);

Route::post('/subscription/create-order', [SubscriptionController::class, 'createOrder']);
Route::post('/subscription/get-payments', [SubscriptionController::class, 'getPaymentsForOrder']);
Route::post('/subscription/charge', [SubscriptionController::class, 'chargeSavedMethod']);
Route::post('/subscription/webhook', [SubscriptionController::class, 'webhook']);

Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);