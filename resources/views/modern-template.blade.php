<!-- resume-template.blade.php -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CV - {{ $resumeData['candidateName'][0]['firstName'] }} {{ $resumeData['candidateName'][0]['familyName'] }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
            background-color: #fff;
        }
        .container {
            width: 100%;
            display: table;
        }
        .sidebar {
            width: 35%;
            float: left;
            background-color: #8B4444;
            color: white;
            padding: 20px;
            height: 100%;
        }
        .content {
            width: 65%;
            float: left;
            padding: 20px;
            background-color: #f9f9f9;
        }
        h1, h2 {
            color: #2C5F9E;
        }
        .section-title {
            border-bottom: 1px solid #2C5F9E;
            margin-bottom: 10px;
            padding-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <h1>{{ $resumeData['candidateName'][0]['firstName'] }} {{ $resumeData['candidateName'][0]['familyName'] }}</h1>
            <p>Email: {{ $resumeData['email'][0] }}</p>
            <p>Phone: {{ $resumeData['phoneNumber'][0]['formattedNumber'] }}</p>
            <p>Location: {{ $resumeData['location']['city'] }}, {{ $resumeData['location']['country'] }}</p>

            <h2 class="section-title">Languages</h2>
            <ul>
                @foreach ($resumeData['languages'] as $lang)
                    <li>{{ $lang['name'] }}</li>
                @endforeach
            </ul>
        </div>

        <!-- Main content -->
        <div class="content">
            <h2 class="section-title">Professional Summary</h2>
            <p>{{ $resumeData['summary']['paragraph'] }}</p>

            <h2 class="section-title">Employment</h2>
            @foreach ($resumeData['workExperience'] as $job)
                <h3>{{ $job['workExperienceJobTitle'] }} - {{ $job['workExperienceOrganization'] }}</h3>
                <p>{{ $job['workExperienceDates']['start']['date'] }} - {{ $job['workExperienceDates']['end']['date'] }}</p>
                <p>{{ $job['workExperienceDescription'] }}</p>
                <ul>
                    @foreach ($job['highlights']['items'] as $highlight)
                        <li>{{ $highlight['bullet'] }}</li>
                    @endforeach
                </ul>
            @endforeach

            <h2 class="section-title">Education</h2>
            @foreach ($resumeData['education'] as $edu)
                <p><strong>{{ $edu['educationLevel']['label'] }}</strong> — {{ $edu['educationOrganization'] }} ({{ $edu['educationDates']['start']['date'] }} - {{ $edu['educationDates']['end']['date'] }})</p>
            @endforeach
        </div>
    </div>
</body>
</html>