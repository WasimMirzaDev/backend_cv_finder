<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $resumeData['candidateName'][0]['firstName'] ?? '' }} {{ $resumeData['candidateName'][0]['familyName'] ?? '' }} - Resume</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
            margin: 0;
            padding: 40px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
        }
        .name {
            font-size: 26px;
            font-weight: bold;
        }
        .headline {
            font-size: 14px;
            margin-top: 5px;
        }
        .contact {
            margin-top: 10px;
        }
        .section {
            margin-top: 25px;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            border-bottom: 1px solid #aaa;
            padding-bottom: 3px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        .entry {
            margin-bottom: 10px;
        }
        .entry-title {
            font-weight: bold;
        }
        ul {
            padding-left: 18px;
            margin: 5px 0;
        }
        li {
            margin-bottom: 3px;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="name">
            {{ $resumeData['candidateName'][0]['firstName'] ?? '' }} {{ $resumeData['candidateName'][0]['familyName'] ?? '' }}
        </div>
        <div class="headline">
            {{ $resumeData['headline'] ?? '' }}
        </div>
        <div class="contact">
            {{ $resumeData['email'][0] ?? '' }} | 
            {{ $resumeData['phoneNumber'][0]['formattedNumber'] ?? '' }} | 
            {{ $resumeData['location']['city'] ?? '' }}, {{ $resumeData['location']['state'] ?? '' }}, {{ $resumeData['location']['country'] ?? '' }}
        </div>
    </div>

    @if(!empty($resumeData['summary']['paragraph']))
    <div class="section">
        <div class="section-title">Summary</div>
        <div class="entry">
            {{ $resumeData['summary']['paragraph'] }}
        </div>
    </div>
    @endif

    @if(!empty($resumeData['skill']))
    <div class="section">
        <div class="section-title">Skills</div>
        <div class="entry">
            <ul>
                @foreach($resumeData['skill'] as $skill)
                    <li>{{ $skill['name'] }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    @if(!empty($resumeData['education']))
    <div class="section">
        <div class="section-title">Education</div>
        @foreach($resumeData['education'] as $edu)
            <div class="entry">
                <div class="entry-title">{{ $edu['educationOrganization'] ?? '' }}</div>
                <div>{{ $edu['educationLevel']['label'] ?? '' }}</div>
                <div>{{ $edu['educationDates']['start']['date'] ?? '' }} - {{ $edu['educationDates']['end']['date'] ?? '' }}</div>
            </div>
        @endforeach
    </div>
    @endif

    @if(!empty($resumeData['workExperience']))
    <div class="section">
        <div class="section-title">Work Experience</div>
        @foreach($resumeData['workExperience'] as $job)
            <div class="entry">
                <div class="entry-title">
                    {{ $job['workExperienceJobTitle'] ?? '' }} - {{ $job['workExperienceOrganization'] ?? '' }}
                </div>
                <div>
                    {{ $job['workExperienceDates']['start']['date'] ?? '' }} - {{ $job['workExperienceDates']['end']['date'] ?? 'Present' }}
                </div>
                <div>{{ $job['workExperienceDescription'] ?? '' }}</div>

                @if(!empty($job['highlights']['items']))
                    <ul>
                        @foreach($job['highlights']['items'] as $highlight)
                            <li>{{ $highlight['bullet'] ?? '' }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </div>
    @endif

    @if(!empty($resumeData['languages']))
    <div class="section">
        <div class="section-title">Languages</div>
        <ul>
            @foreach($resumeData['languages'] as $lang)
                <li>{{ $lang['name'] ?? '' }} — {{ $lang['level'] ?? '' }}</li>
            @endforeach
        </ul>
    </div>
    @endif

</body>
</html>