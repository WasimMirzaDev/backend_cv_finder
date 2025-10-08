<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $resumeData['candidateName'][0]['firstName'] ?? '' }} {{ $resumeData['candidateName'][0]['familyName'] ?? '' }} - Resume</title>
    <meta name="description" content="Professional CV of {{ $resumeData['candidateName'][0]['firstName'] ?? '' }} {{ $resumeData['candidateName'][0]['familyName'] ?? '' }} - {{ $resumeData['headline'] ?? '' }}">
    <style>
        body { 
            margin: 0; 
            padding: 0; 
            font-family: Arial, sans-serif; 
            background-color: #f5f5f5; 
            font-size: 13px;
        }
        .container { 
            max-width: 900px; 
            margin: 0 auto; 
            background-color: white; 
        }
        .main-table {
            width: 100%;
            border-collapse: collapse;
        }
        .sidebar {
            width: 35%;
            background: #8B4444;
            color: white;
            vertical-align: top;
            position: relative;
        }
        .content {
            width: 65%;
            background-color: #FAFAFA;
            vertical-align: top;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            color: #2C5F9E;
            font-size: 18px;
            font-weight: bold;
            margin: 0 0 15px 0;
            border-bottom: 2px solid #2C5F9E;
            padding-bottom: 5px;
        }
        .sidebar-title {
            font-size: 16px;
            font-weight: bold;
            margin: 0 0 12px 0;
            padding-bottom: 5px;
            border-bottom: 2px solid rgba(255,255,255,0.3);
        }
        .personal-info {
            font-size: 13px;
        }
        .personal-info p {
            margin: 8px 0;
        }
        .job-item {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        .job-header {
            margin-bottom: 8px;
        }
        .job-title {
            color: #333;
            font-size: 15px;
            font-weight: bold;
            margin: 0;
        }
        .job-date {
            color: #666;
            font-size: 12px;
        }
        .job-company {
            color: #2C5F9E;
            font-size: 12px;
            font-style: italic;
            margin: 0 0 8px 0;
        }
        .job-description {
            font-size: 13px;
            color: #333;
            margin: 0 0 10px 0;
            text-align: justify;
        }
        .achievements-title {
            color: #333;
            font-size: 14px;
            font-weight: bold;
            margin: 0 0 8px 0;
        }
        .achievements-list {
            margin: 0;
            padding-left: 18px;
        }
        .achievements-list li {
            margin-bottom: 4px;
            font-size: 13px;
        }
        .education-item {
            margin-bottom: 10px;
        }
        .dots {
            position: absolute;
            bottom: 20px;
            left: 20px;
        }
        .dot {
            width: 6px;
            height: 6px;
            background-color: rgba(255,255,255,0.4);
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: #D4B5C8;
            border: 4px solid rgba(255,255,255,0.3);
            margin: 0 auto;
        }
        .text-center {
            text-align: center;
        }
        .padding-40 {
            padding: 40px;
        }
        .padding-30 {
            padding: 30px;
        }
        .margin-bottom-30 {
            margin-bottom: 30px;
        }
        .margin-bottom-20 {
            margin-bottom: 20px;
        }
        .margin-bottom-15 {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <table class="main-table">
            <tr>
                <!-- Left Sidebar -->
                <td class="sidebar" valign="top">
                    <div class="padding-30">
                        <!-- Decorative dots -->
                        <div class="dots">
                            <span class="dot"></span>
                            <span class="dot"></span>
                            <span class="dot"></span>
                            <span class="dot"></span>
                            <span class="dot"></span>
                        </div>
                        
                        <!-- Name and Photo -->
                        <table width="100%" cellpadding="0" cellspacing="0" class="margin-bottom-30">
                            <tr>
                                <td class="text-center">
                                    <h1 style="margin: 0 0 20px 0; font-size: 24px; font-weight: bold;">
                                        {{ $resumeData['candidateName'][0]['firstName'] ?? '' }} {{ $resumeData['candidateName'][0]['familyName'] ?? '' }}
                                    </h1>
                                    <div class="profile-image">
                                        @if(!empty($resumeData['profileImage']))
                                            <img src="{{ $resumeData['profileImage'] }}" alt="Profile Image" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        </table>

                        <!-- Personal Details -->
                        <div class="section margin-bottom-30">
                            <h2 class="sidebar-title">Personal details</h2>
                            <div class="personal-info">
                                @if(!empty($resumeData['candidateName']))
                                <p>
                                    <strong>{{ $resumeData['candidateName'][0]['firstName'] ?? '' }} {{ $resumeData['candidateName'][0]['familyName'] ?? '' }}</strong>
                                </p>
                                @endif
                                @if(!empty($resumeData['email'][0]))
                                <p>{{ $resumeData['email'][0] }}</p>
                                @endif
                                @if(!empty($resumeData['phoneNumber'][0]['formattedNumber']))
                                <p>{{ $resumeData['phoneNumber'][0]['formattedNumber'] }}</p>
                                @endif
                                @if(!empty($resumeData['location']))
                                <p>
                                    {{ $resumeData['location']['city'] ?? '' }}
                                    @if(!empty($resumeData['location']['country']))
                                    <br>{{ $resumeData['location']['country'] }}
                                    @endif
                                </p>
                                @endif
                            </div>
                        </div>

                        <!-- Languages -->
                        <div class="section margin-bottom-30">
                            @if(!empty($resumeData['languages']))
                            <h2 class="sidebar-title">Languages</h2>
                            <div class="personal-info">
                                @foreach($resumeData['languages'] as $language)
                                <p>{{ $language['name'] ?? '' }} {{ !empty($language['proficiency']) ? '('.$language['proficiency'].')' : '' }}</p>
                                @endforeach
                            </div>
                            @endif
                        </div>

                        <!-- Hobbies -->
                        <div class="section">
                            @if(!empty($resumeData['hobbies']))
                            <h2 class="sidebar-title">Hobbies</h2>
                            <div class="personal-info">
                                @foreach($resumeData['hobbies'] as $hobby)
                                <p>{{ $hobby }}</p>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>
                </td>

                <!-- Right Content -->
                <td class="content" valign="top">
                    <div class="padding-40">
                        <!-- Professional Summary -->
                        <div class="section margin-bottom-30">
                            <h2 class="section-title">Professional Summary</h2>
                            @if(!empty($resumeData['summary']['paragraph']))
                            <p class="job-description">
                                {{ $resumeData['summary']['paragraph'] }}
                            </p>
                            @endif
                        </div>

                        <!-- Employment -->
                        @if(!empty($resumeData['workExperience']))
                        <div class="section margin-bottom-30">
                            <h2 class="section-title">Employment</h2>
                            @foreach($resumeData['workExperience'] as $job)
                            <div class="job-item">
                                <table width="100%" class="job-header">
                                    <tr>
                                        <td>
                                            <h3 class="job-title">{{ $job['workExperienceJobTitle'] ?? 'Position' }}</h3>
                                        </td>
                                        <td align="right">
                                            <span class="job-date">
                                                {{ $job['workExperienceDates']['start']['date'] ?? '' }}
                                                @if(!empty($job['workExperienceDates']['end']['date']))
                                                    - {{ $job['workExperienceDates']['end']['date'] }}
                                                @elseif(empty($job['workExperienceDates']['end']['date']) && !empty($job['workExperienceDates']['start']['date']))
                                                    - Present
                                                @endif
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                                @if(!empty($job['workExperienceOrganization']))
                                <p class="job-company">
                                    {{ $job['workExperienceOrganization'] }}
                                    @if(!empty($job['location']))
                                        , {{ $job['location'] }}
                                    @endif
                                </p>
                                @endif
                                @if(!empty($job['workExperienceDescription']))
                                <p class="job-description">
                                    {{ $job['workExperienceDescription'] }}
                                </p>
                                @endif
                                @if(!empty($job['highlights']['items']))
                                <div>
                                    <h4 class="achievements-title">Key Achievements</h4>
                                    <ul class="achievements-list">
                                        @foreach($job['highlights']['items'] as $achievement)
                                        <li>{{ $achievement['bullet'] }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                        @endif

                        <!-- Education -->
                        @if(!empty($resumeData['education']))
                        <div class="section">
                            <h2 class="section-title">Education</h2>
                            @foreach($resumeData['education'] as $education)
                            <div class="education-item">
                                <table width="100%">
                                    <tr>
                                        <td>
                                            <h3 class="job-title">{{ $education['educationLevel']['label'] ?? $education['educationAccreditation'] ?? 'Degree' }}</h3>
                                        </td>
                                        <td align="right">
                                            <span class="job-date">
                                                {{ $education['educationDates']['start']['date'] ?? '' }}
                                                @if(!empty($education['educationDates']['end']['date']))
                                                    - {{ $education['educationDates']['end']['date'] }}
                                                @elseif(empty($education['educationDates']['end']['date']) && !empty($education['educationDates']['start']['date']))
                                                    - Present
                                                @endif
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                                @if(!empty($education['educationOrganization']))
                                <p class="job-company">
                                    {{ $education['educationOrganization'] }}
                                    @if(!empty($education['location']))
                                        , {{ $education['location'] }}
                                    @endif
                                </p>
                                @endif
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>