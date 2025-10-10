<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $resumeData['candidateName'][0]['firstName'] ?? '' }} {{ $resumeData['candidateName'][0]['familyName'] ?? '' }} - Resume</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <style>
        body { font-family: Inter, Arial, sans-serif; line-height: 1.2; margin: 0; padding: 0; color: #000; }
        .resume { max-width: 8in; margin: 0 auto; padding: 0.6in; }
        .header { margin-bottom: 15px; padding-bottom: 10px; }
        .contact-info { font-size: 14px; margin-bottom: 5px; color: #878787; }
        .section { margin-bottom: 15px; font-size: 14px; }
        .section-title { font-size: 15px; font-weight: bold; margin-bottom: 5px; color: rgb(12, 170, 219); }
        .experience-item { margin-bottom: 10px; font-size: 14px; }
        .job-title { font-weight: bold; margin-bottom: 4px; font-size: 14px; color: rgb(12, 170, 219); }
        .company { font-weight: bold; color: rgb(12, 170, 219); }
        .date { font-size: 11px; margin-bottom: 10px; display: block; color: #878787; }
        .responsibilities { margin-top: 5px; padding-left: 15px; }
        .responsibilities li { margin-bottom: 2px; font-size: 14px; }
        .page-break { page-break-before: always; margin-top: 40px; }
        strong { font-weight: 500; }
    </style>
</head>
<body>
    <div class="resume">
        <!-- Header -->
        <div class="header">
            <div class="contact-info">
                {{ $resumeData['candidateName'][0]['firstName'] ?? '' }} {{ $resumeData['candidateName'][0]['familyName'] ?? '' }} |
                {{ $resumeData['location']['city'] ?? '' }}, {{ $resumeData['location']['country'] ?? '' }} |
                {{ $resumeData['email'][0] ?? '' }} | 
                {{ $resumeData['phoneNumber'][0]['formattedNumber'] ?? '' }}
            </div>
        </div>

        <!-- Headline -->
        <div class="section">
            <div class="section-title">Candidate Headline</div>
            <strong>{{ $resumeData['headline'] ?? '' }}</strong>
        </div>

        <!-- Profile -->
        <div class="section">
            <div class="section-title">Profile</div>
            <div>{{ $resumeData['summary']['paragraph'] ?? '' }}</div>
        </div>

        <!-- Skills -->
        @if(!empty($resumeData['skill']))
        <div class="section">
            <div class="section-title">Key Skills</div>
            <ul class="responsibilities">
                @foreach($resumeData['skill'] as $skill)
                    <li>{{ $skill['name'] }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- Experience -->
        @if(!empty($resumeData['workExperience']))
        <div class="section">
            <div class="section-title">Experience</div>
            @foreach($resumeData['workExperience'] as $job)
            <div class="experience-item">
                <div class="job-title">
                    {{ $job['workExperienceJobTitle'] ?? '' }} |
                    <span class="company">{{ $job['workExperienceOrganization'] ?? '' }}</span>
                </div>
                <div class="date">
                    {{ $job['workExperienceDates']['start']['date'] ?? '' }} -
                    {{ $job['workExperienceDates']['end']['date'] ?? 'Present' }}
                </div>
                <div>{{ $job['workExperienceDescription'] ?? '' }}</div>
                @if(!empty($job['highlights']['items']))
                <ul class="responsibilities">
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
            <div class="experience-item">
                <div class="job-title">{{ $edu['educationLevel']['label'] ?? '' }} |
                    <span class="company">{{ $edu['educationOrganization'] ?? '' }}</span>
                </div>
                <div class="date">
                    {{ $edu['educationDates']['start']['date'] ?? '' }} -
                    {{ $edu['educationDates']['end']['date'] ?? '' }}
                </div>
            </div>
            @endforeach
        </div>
        @endif

        <!-- Languages -->
        @if(!empty($resumeData['languages']))
        <div class="section">
            <div class="section-title">Languages</div>
            <ul class="responsibilities">
                @foreach($resumeData['languages'] as $lang)
                    <li>{{ $lang['name'] }} ({{ $lang['level'] ?? 'Fluent' }})</li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
</body>
</html>
