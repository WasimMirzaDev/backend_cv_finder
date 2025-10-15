<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $resumeData['candidateName'][0]['firstName'] ?? '' }} {{ $resumeData['candidateName'][0]['familyName'] ?? '' }} - Resume</title>
    <link href="https://fonts.googleapis.com/css2?family=Epilogue:wght@100..900&family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <style>
        body { font-family: Inter, "Times New Roman", serif; font-size: 14px; line-height: 1.2; margin: 0; padding: 0; color: #5a5a5a; background-color: #fff; }
        .resume { max-width: 8in; margin: 0 auto; padding: 0.6in; }
        .name { color: #000; font-size: 24px; font-weight: bold; margin-bottom: 12px; }
        .headline { font-size: 13px; margin-bottom: 15px; font-weight: normal; }
        .section { margin-bottom: 15px; }
        .section-title { font-size: 14px; font-weight: bold; margin-bottom: 8px; border-bottom: 1px solid #cecbcb; padding-bottom: 2px; text-transform: uppercase; color: #000; }
        .personal-details { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .personal-details td { padding: 2px 0; vertical-align: top; }
        .personal-details td:first-child { font-weight: bold; width: 30%; color: #000; }
        .employment-item { margin-bottom: 15px; }
        .employment-date { font-weight: 500; margin-bottom: 3px; font-size: 12px; color: #000; }
        .job-title { font-weight: 600; margin-bottom: 4px; color: #000; font-size: 15px; }
        .company { font-weight: 600; margin-bottom: 5px; color: #000; font-size: 15px; }
        .job-description { margin-bottom: 8px; }
        .achievements-title { font-weight: 600; margin: 8px 0 5px; color: #000; }
        .achievements { padding-left: 15px; margin-bottom: 5px; }
        .achievements li { margin-bottom: 3px; }
        .page-break { page-break-before: always; margin-top: 30px; }
        .skills { padding-left: 15px; }
        .skills li { margin-bottom: 3px; }
    </style>
</head>
<body>
    <div class="resume">
        <!-- Name -->
        <div class="name">
            {{ $resumeData['candidateName'][0]['firstName'] ?? '' }}
            {{ $resumeData['candidateName'][0]['familyName'] ?? '' }}
        </div>

        <!-- Headline -->
        <div class="headline"><em>{{ $resumeData['headline'] ?? '' }}</em></div>

        <!-- Personal Details -->
        <div class="section">
            <div class="section-title">Personal details</div>
            <table class="personal-details">
                <tr><td>Name</td><td>{{ $resumeData['candidateName'][0]['firstName'] ?? '' }} {{ $resumeData['candidateName'][0]['familyName'] ?? '' }}</td></tr>
                <tr><td>Email address</td><td>{{ $resumeData['email'][0] ?? '' }}</td></tr>
                <tr><td>Phone number</td><td>{{ $resumeData['phoneNumber'][0]['formattedNumber'] ?? '' }}</td></tr>
                <tr><td>Address</td><td>{{ $resumeData['location']['formatted'] ?? '' }}</td></tr>
                <tr><td>City</td><td>{{ $resumeData['location']['city'] ?? '' }}</td></tr>
            </table>
        </div>

        <!-- Profile -->
        <div class="section">
            <div class="section-title">Profile</div>
            <div>{{ $resumeData['summary']['paragraph'] ?? '' }}</div>
        </div>

        <!-- Employment -->
        @if(!empty($resumeData['workExperience']))
        <div class="section">
            <div class="section-title">Employment</div>
            @foreach($resumeData['workExperience'] as $job)
            <div class="employment-item">
                <div class="employment-date">
                    {{ $job['workExperienceDates']['start']['date'] ?? '' }} - {{ $job['workExperienceDates']['end']['date'] ?? 'Present' }}
                </div>
                <div class="job-title">{{ $job['workExperienceJobTitle'] ?? '' }}</div>
                <div class="company">{{ $job['workExperienceOrganization'] ?? '' }}</div>
                <div class="job-description">{{ $job['workExperienceDescription'] ?? '' }}</div>

                @if(!empty($job['highlights']['items']))
                <div class="achievements-title">Key Achievements</div>
                <ul class="achievements">
                    @foreach($job['highlights']['items'] as $point)
                        <li>{{ $point['bullet'] }}</li>
                    @endforeach
                </ul>
                @endif
            </div>
            @endforeach
        </div>
        @endif

        <!-- Education -->
        @if(!empty($resumeData['education']))
        <div class="section">
            <div class="section-title">Education</div>
            @foreach($resumeData['education'] as $edu)
            <div class="employment-item">
                <div class="employment-date">
                    {{ $edu['educationDates']['start']['date'] ?? '' }} - {{ $edu['educationDates']['end']['date'] ?? '' }}
                </div>
                <div class="job-title">{{ $edu['educationLevel']['label'] ?? '' }}</div>
                <div class="company">{{ $edu['educationOrganization'] ?? '' }}</div>
            </div>
            @endforeach
        </div>
        @endif

        <!-- Skills -->
        @if(!empty($resumeData['skill']))
        <div class="section">
            <div class="section-title">Skills</div>
            <ul class="skills">
                @foreach($resumeData['skill'] as $skill)
                    <li>{{ $skill['name'] }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- Languages -->
        @if(!empty($resumeData['languages']))
        <div class="section">
            <div class="section-title">Languages</div>
            <ul class="skills">
                @foreach($resumeData['languages'] as $lang)
                    <li>{{ $lang['name'] }} ({{ $lang['level'] ?? 'Fluent' }})</li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
</body>
</html>
