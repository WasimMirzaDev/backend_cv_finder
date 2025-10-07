<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\Api\ResumeController;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/migrate', function () {
    \Illuminate\Support\Facades\Artisan::call('migrate');
    return "Migrate Complete!";
});

Route::get('/static-file-runner-v1', [ResumeController::class, 'staticFileRunner']);

Route::get('/seed', function () {
    \Illuminate\Support\Facades\Artisan::call('db:seed');
    return "Seed Complete!";
});
Route::get('/optimize-clear', function () {
    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
    return "Optimize Clear Complete!";
});

Route::get('/storage-link', function () {
    if (file_exists(public_path('storage'))) {
        return 'Storage link already exists!';
    }
    
    \Illuminate\Support\Facades\Artisan::call('storage:link');
    return 'Storage link created successfully!';
});



Route::get('/resume/{id}/download', [ResumeController::class, 'download']);

Route::get('/logs', function () {
    $logFile = storage_path('logs/laravel.log');

    if (!File::exists($logFile)) {
        return response('Log file not found.', 404);
    }

    // Read last 500 lines for performance
    $lines = explode("\n", File::get($logFile));
    $lastLines = array_slice($lines, -500);

    return Response::make(
        nl2br(e(implode("\n", $lastLines))),
        200,
        ['Content-Type' => 'text/html']
    );
});


// Test route (no DB, uses hardcoded JSON)
Route::get('/resume/test/download', function () {
    
    $resumeData = json_decode('{
        "candidateName":[{"firstName":"Faraz","familyName":"Mehmood"}],
        "headline":"Passionate web developer with expertise in creating scalable, high-performance applications using modern frameworks and technologies.",
        "email":["farazmehmood2563@gmail.com"],
        "phoneNumber":[{"formattedNumber":"+92 314 2563223"}],
        "location":{"city":"Faisalabad","state":"Punjab","country":"Pakistan"},
        "summary":{"paragraph":"Experienced PHP/Laravel Developer with over 5 years of expertise in building scalable web applications. Proficient in Laravel, MySQL, and RESTful APIs, I have successfully improved application performance by 30% and led a team in developing a high-traffic e-commerce platform."},
        "education":[
            {
                "educationOrganization":"Tips College Of Commerce D Ground Campus",
                "educationDates":{"start":{"date":"2017"},"end":{"date":"2021"}},
                "educationLevel":{"label":"Bachelor\'s Degree"}
            }
        ],
        "workExperience":[
            {
                "workExperienceJobTitle":"Web App Developer",
                "workExperienceOrganization":"YummyApps",
                "workExperienceDates":{"start":{"date":"2024"},"end":{"date":"2025"}},
                "workExperienceDescription":"As a Web App Developer at YummyApps...",
                "highlights":{"items":[
                    {"bullet":"Developed RESTful APIs to enhance application functionality."},
                    {"bullet":"Integrated third-party services to expand capabilities."},
                    {"bullet":"Managed databases with a focus on optimization and scalability."}
                ]}
            }
        ],
        "skill":[
            {"name":"PHP"},{"name":"Laravel"},{"name":"MySQL"},
            {"name":"RESTful APIs"},{"name":"Git"},{"name":"Testing"}
        ],
        "languages":[{"name":"English","level":"Fluent"},{"name":"Urdu","level":"Fluent"}]
    }', true);

    $pdf = Pdf::loadView('modern-template', compact('resumeData'))
              ->setPaper('a4', 'portrait');

    return $pdf->download('resume_test.pdf');
});