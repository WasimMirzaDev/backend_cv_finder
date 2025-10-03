<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Resume</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; line-height: 1.6; color: #333; }
        h2 { color: #2585E0; margin-bottom: 4px; }
        .section-title { font-size: 15px; font-weight: 600; color: #2585E0; margin: 15px 0 6px; }
        .job-title { font-weight: bold; color: #2585E0; }
        .company { font-weight: 500; color: #000; }
        .date { font-size: 13px; color: #777; margin-bottom: 6px; }
        ul { margin: 4px 0 8px 18px; }
    </style>
</head>
<body>
    {{-- Header --}}
    <p>
        {{ $resumeData['candidateName'][0]['firstName'] ?? '' }}
        {{ $resumeData['candidateName'][0]['familyName'] ?? '' }} |
        {{ $resumeData['location']['city'] ?? '' }},
        {{ $resumeData['location']['country'] ?? '' }} |
        {{ $resumeData['email'][0] ?? '' }} |
        {{ $resumeData['phoneNumber'][0]['formattedNumber'] ?? '' }}
    </p>

    {{-- Headline --}}
    <h2>Candidate Headline</h2>
    <p>{{ $resumeData['headline'] ?? '' }}</p>

    {{-- Profile --}}
    <h2 class="section-title">Profile</h2>
    <p>{{ $resumeData['summary']['paragraph'] ?? '' }}</p>

    {{-- Skills --}}
    @if(!empty($resumeData['skill']))
        <h2 class="section-title">Key Skills</h2>
        <ul>
            @foreach($resumeData['skill'] as $skill)
                <li>{{ $skill['name'] }}</li>
            @endforeach
        </ul>
    @endif

    {{-- Experience --}}
    @if(!empty($resumeData['workExperience']))
        <h2 class="section-title">Experience</h2>
        @foreach($resumeData['workExperience'] as $job)
            <div style="margin-bottom:12px;">
                <p class="job-title">
                    {{ $job['workExperienceJobTitle'] ?? '' }} | {{ $job['workExperienceOrganization'] ?? '' }}
                </p>
                <p class="date">
                    {{ $job['workExperienceDates']['start']['date'] ?? '' }}
                    -
                    {{ $job['workExperienceDates']['end']['date'] ?? 'Present' }}
                </p>
                <p>{{ $job['workExperienceDescription'] ?? '' }}</p>
                @if(!empty($job['highlights']['items']))
                    <ul>
                        @foreach($job['highlights']['items'] as $point)
                            <li>{{ $point['bullet'] }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    @endif

    {{-- Education --}}
    @if(!empty($resumeData['education']))
        <h2 class="section-title">Education</h2>
        @foreach($resumeData['education'] as $edu)
            <div style="margin-bottom:12px;">
                <p class="job-title">{{ $edu['educationLevel']['label'] ?? '' }} | {{ $edu['educationOrganization'] ?? '' }}</p>
                <p class="date">
                    {{ $edu['educationDates']['start']['date'] ?? '' }}
                    -
                    {{ $edu['educationDates']['end']['date'] ?? '' }}
                </p>
            </div>
        @endforeach
    @endif

    {{-- Languages --}}
    @if(!empty($resumeData['languages']))
        <h2 class="section-title">Languages</h2>
        <ul>
            @foreach($resumeData['languages'] as $lang)
                <li>{{ $lang['name'] }} ({{ $lang['level'] }})</li>
            @endforeach
        </ul>
    @endif
</body>
</html>