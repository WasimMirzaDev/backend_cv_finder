```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $resumeData['candidateName'][0]['firstName'] ?? '' }} {{ $resumeData['candidateName'][0]['familyName'] ?? '' }} - Professional CV</title>
    <meta name="description" content="Professional CV of {{ $resumeData['candidateName'][0]['firstName'] ?? '' }} {{ $resumeData['candidateName'][0]['familyName'] ?? '' }}">
</head>

<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f5f5f5;">
    <div style="max-width: 900px; margin: 0 auto; background-color: white; display: flex; min-height: 100vh;">
        
        <!-- Left Sidebar -->
        <div style="width: 35%; background: linear-gradient(180deg, #8B4444 0%, #6B3333 100%); color: white; padding: 40px 30px; position: relative;">

            <!-- Decorative dots -->
            <div style="position: absolute; bottom: 20px; left: 20px; display: flex; gap: 8px;">
                @for($i=0; $i<5; $i++)
                    <span style="width: 6px; height: 6px; background-color: rgba(255,255,255,0.4); border-radius: 50%; display: inline-block;"></span>
                @endfor
            </div>

            <!-- Name & Photo -->
            <div style="text-align: center; margin-bottom: 40px;">
                <h1 style="margin: 0 0 20px 0; font-size: 24px; font-weight: bold;">
                    {{ $resumeData['candidateName'][0]['firstName'] ?? '' }} {{ $resumeData['candidateName'][0]['familyName'] ?? '' }}
                </h1>

                @if(!empty($resumeData['profileImage']))
                    <img src="{{ $resumeData['profileImage'] }}" alt="Profile" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid rgba(255,255,255,0.3);">
                @else
                    <div style="width: 120px; height: 120px; border-radius: 50%; background-color: #D4B5C8; margin: 0 auto; border: 4px solid rgba(255,255,255,0.3);"></div>
                @endif
            </div>

            <!-- Personal Details -->
            <div style="margin-bottom: 35px;">
                <h2 style="font-size: 16px; font-weight: bold; margin: 0 0 15px 0; padding-bottom: 8px; border-bottom: 2px solid rgba(255,255,255,0.3);">
                    Personal details
                </h2>
                <div style="font-size: 13px; line-height: 1.8;">
                    @if(!empty($resumeData['candidateName'][0]['firstName']))
                        <p style="margin: 8px 0;">👤 {{ $resumeData['candidateName'][0]['firstName'] }} {{ $resumeData['candidateName'][0]['familyName'] ?? '' }}</p>
                    @endif
                    @if(!empty($resumeData['email'][0]))
                        <p style="margin: 8px 0;">✉️ {{ $resumeData['email'][0] }}</p>
                    @endif
                    @if(!empty($resumeData['phoneNumber'][0]['formattedNumber']))
                        <p style="margin: 8px 0;">📱 {{ $resumeData['phoneNumber'][0]['formattedNumber'] }}</p>
                    @endif
                    @if(!empty($resumeData['location']['city']) || !empty($resumeData['location']['country']))
                        <p style="margin: 8px 0;">📍 {{ $resumeData['location']['city'] ?? '' }}<br>{{ $resumeData['location']['country'] ?? '' }}</p>
                    @endif
                </div>
            </div>

            <!-- Languages -->
            @if(!empty($resumeData['languages']))
            <div style="margin-bottom: 35px;">
                <h2 style="font-size: 16px; font-weight: bold; margin: 0 0 15px 0; padding-bottom: 8px; border-bottom: 2px solid rgba(255,255,255,0.3);">Languages</h2>
                <div style="font-size: 13px; line-height: 1.8;">
                    @foreach($resumeData['languages'] as $lang)
                        <p style="margin: 8px 0;">{{ $lang['name'] ?? '' }} ({{ $lang['level'] ?? 'Fluent' }})</p>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Hobbies -->
            @if(!empty($resumeData['hobbies']))
            <div style="margin-bottom: 35px;">
                <h2 style="font-size: 16px; font-weight: bold; margin: 0 0 15px 0; padding-bottom: 8px; border-bottom: 2px solid rgba(255,255,255,0.3);">Hobbies</h2>
                <div style="font-size: 13px; line-height: 1.8;">
                    @foreach($resumeData['hobbies'] as $hobby)
                        <p style="margin: 8px 0;">{{ $hobby }}</p>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Right Content -->
        <div style="width: 65%; padding: 40px 45px; background-color: #FAFAFA;">
            
            <!-- Professional Summary -->
            @if(!empty($resumeData['summary']['paragraph']))
            <section style="margin-bottom: 35px;">
                <h2 style="color: #2C5F9E; font-size: 20px; font-weight: bold; margin: 0 0 15px 0; border-bottom: 2px solid #2C5F9E; padding-bottom: 8px;">
                    Professional Summary
                </h2>
                <p style="font-size: 13px; line-height: 1.7; color: #333; margin: 0; text-align: justify;">
                    {{ $resumeData['summary']['paragraph'] }}
                </p>
            </section>
            @endif

            <!-- Employment -->
            @if(!empty($resumeData['workExperience']))
            <section style="margin-bottom: 35px;">
                <h2 style="color: #2C5F9E; font-size: 20px; font-weight: bold; margin: 0 0 20px 0; border-bottom: 2px solid #2C5F9E; padding-bottom: 8px;">
                    Employment
                </h2>

                @foreach($resumeData['workExperience'] as $job)
                <div style="margin-bottom: 30px;">
                    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 10px;">
                        <h3 style="color: #333; font-size: 15px; font-weight: bold; margin: 0;">{{ $job['workExperienceJobTitle'] ?? '' }}</h3>
                        <span style="color: #666; font-size: 12px;">{{ $job['workExperienceDates']['start']['date'] ?? '' }} - {{ $job['workExperienceDates']['end']['date'] ?? 'Present' }}</span>
                    </div>
                    <p style="color: #2C5F9E; font-size: 12px; margin: 0 0 12px 0; font-style: italic;">
                        {{ $job['workExperienceOrganization'] ?? '' }}{{ !empty($job['workExperienceLocation']['city']) ? ', '.$job['workExperienceLocation']['city'] : '' }}
                    </p>
                    <p style="font-size: 13px; line-height: 1.7; color: #333; margin: 0 0 12px 0; text-align: justify;">
                        {{ $job['workExperienceDescription'] ?? '' }}
                    </p>

                    @if(!empty($job['highlights']['items']))
                    <div>
                        <h4 style="color: #333; font-size: 14px; font-weight: bold; margin: 0 0 10px 0;">Key Achievements</h4>
                        <ul style="margin: 0; padding-left: 20px; font-size: 13px; line-height: 1.8; color: #333;">
                            @foreach($job['highlights']['items'] as $point)
                                <li>{{ $point['bullet'] }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </div>
                @endforeach
            </section>
            @endif

            <!-- Education -->
            @if(!empty($resumeData['education']))
            <section style="margin-bottom: 20px;">
                <h2 style="color: #2C5F9E; font-size: 20px; font-weight: bold; margin: 0 0 20px 0; border-bottom: 2px solid #2C5F9E; padding-bottom: 8px;">
                    Education
                </h2>
                @foreach($resumeData['education'] as $edu)
                <div style="margin-bottom: 15px;">
                    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 5px;">
                        <h3 style="color: #333; font-size: 15px; font-weight: bold; margin: 0;">{{ $edu['educationLevel']['label'] ?? '' }}</h3>
                        <span style="color: #666; font-size: 12px;">{{ $edu['educationDates']['start']['date'] ?? '' }} - {{ $edu['educationDates']['end']['date'] ?? '' }}</span>
                    </div>
                    <p style="color: #2C5F9E; font-size: 12px; margin: 0; font-style: italic;">
                        {{ $edu['educationOrganization'] ?? '' }}
                    </p>
                </div>
                @endforeach
            </section>
            @endif
        </div>
    </div>
</body>
</html>
```
