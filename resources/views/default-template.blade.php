<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ $resumeData['candidateName'][0]['firstName'] ?? '' }} {{ $resumeData['candidateName'][0]['familyName'] ?? '' }} — Resume</title>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    :root {
      --bg: #ffffff;
      --text: #1c1e21;
      --muted: #0000;
      --border: #e5e7eb;
      --heading: #0f172a;
      --brand: #7a3145;
      --accent: #f3f4f6;
      --link: #0ea5e9;
      --max-width: 900px;
    }

    * { box-sizing: border-box; }

    html, body {
      margin: 0;
      padding: 0;
      background: var(--bg);
      color: var(--text);
      font-family: 'Inter', system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
      line-height: 1.55;
    }

    .resume {
      margin: 24px auto 80px;
      padding: 0 24px;
      max-width: var(--max-width);
    }

    .header {
      display: grid;
      grid-template-columns: 56px 1fr;
      align-items: center;
      margin-top: 8px;
      gap: 2px;
    }

    .brand-mark {
      width: 50px;
      height: 50px;
      background: var(--brand);
      border-radius: 2px;
    }

    .name {
      font-size: 28px;
      color: var(--brand);
      font-weight: 700;
      margin: 0;
    }

    .section-title {
      font-size: 15px;
      font-weight: bold;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: #000;
      margin: 24px 0 12px;
      border-bottom: 1px solid var(--border);
      padding-bottom: 10px;
    }

    .personal-grid {
      border-bottom: 1px solid var(--border);
      border-radius: 6px;
      padding: 16px;
      width: 60%;
    }

    .field {
      display: flex;
      gap: 30px;
      align-items: center;
    }

    .field .label {
      font-size: 14px;
      color: var(--brand);
      font-weight: bold;
      width: 25%;
    }

    .field .value {
      font-size: 14px;
    }

    .summary p {
      margin: 0;
      padding: 5px;
      border-radius: 6px;
    }

    .job { padding: 10px 0 8px; }

    .job-header {
      display: grid;
      grid-template-columns: 1fr auto;
      align-items: center;
      margin-bottom: 8px;
    }

    .role { font-weight: 600; color: var(--heading); }

    .company { color: #000; font-weight: bold; }

    .daterange { color: var(--brand); font-size: 14px; font-weight: bold; }

    .subgrid { display: grid; grid-template-columns: 1fr; gap: 6px 16px; }

    .job ul {
      margin: 0;
      padding: 0 17px;
    }

    .footer {
      margin-top: 24px;
      color: var(--muted);
      font-size: 12px;
    }

    @media print {
      html, body { background: #fff; color: #000; }
      .resume { margin: 0 auto; padding: 0 16mm; max-width: 180mm; }
    }
  </style>
</head>

<body>
  <main class="resume" role="main">
    <!-- Header -->
    <header class="header">
      <div class="brand-mark" aria-hidden="true"></div>
      <div>
        <h1 class="name">{{ $resumeData['candidateName'][0]['firstName'] ?? '' }} {{ $resumeData['candidateName'][0]['familyName'] ?? '' }}</h1>
      </div>
    </header>

    <!-- Personal Info -->
    <section class="personal">
      <h2 class="section-title">Personal Details</h2>
      <div class="personal-grid">
        @if(!empty($resumeData['email'][0]))
        <div class="field">
          <div class="label">Email</div>
          <div class="value"><a href="mailto:{{ $resumeData['email'][0] }}">{{ $resumeData['email'][0] }}</a></div>
        </div>
        @endif
        @if(!empty($resumeData['phoneNumber'][0]['formattedNumber']))
        <div class="field">
          <div class="label">Phone</div>
          <div class="value"><a href="tel:{{ $resumeData['phoneNumber'][0]['formattedNumber'] }}">{{ $resumeData['phoneNumber'][0]['formattedNumber'] }}</a></div>
        </div>
        @endif
        @if(!empty($resumeData['location']['city']))
        <div class="field">
          <div class="label">City</div>
          <div class="value">{{ $resumeData['location']['city'] ?? '' }}</div>
        </div>
        @endif
        @if(!empty($resumeData['location']['country']))
        <div class="field">
          <div class="label">Country</div>
          <div class="value">{{ $resumeData['location']['country'] ?? '' }}</div>
        </div>
        @endif
      </div>
    </section>

    <!-- Summary -->
    @if(!empty($resumeData['summary']['paragraph']))
    <section class="summary">
      <h2 class="section-title">Summary</h2>
      <p>{{ $resumeData['summary']['paragraph'] }}</p>
    </section>
    @endif

    <!-- Skills -->
    @if(!empty($resumeData['skill']))
    <section class="skills">
      <h2 class="section-title">Key Skills</h2>
      <ul class="job">
        @foreach($resumeData['skill'] as $skill)
          <li>{{ $skill['name'] }}</li>
        @endforeach
      </ul>
    </section>
    @endif

    <!-- Experience -->
    @if(!empty($resumeData['workExperience']))
    <section class="experience">
      <h2 class="section-title">Experience</h2>
      @foreach($resumeData['workExperience'] as $job)
      <article class="job">
        <header class="job-header">
          <div class="job-meta">
            <div class="daterange">
              {{ $job['workExperienceDates']['start']['date'] ?? '' }} — {{ $job['workExperienceDates']['end']['date'] ?? 'Present' }}
            </div>
            <div class="role">{{ $job['workExperienceJobTitle'] ?? '' }}</div>
            <div class="company">{{ $job['workExperienceOrganization'] ?? '' }}</div>
          </div>
        </header>
        <div class="job-body">
          <p>{{ $job['workExperienceDescription'] ?? '' }}</p>
          @if(!empty($job['highlights']['items']))
          <div class="subgrid">
            <ul>
              @foreach($job['highlights']['items'] as $point)
                <li>{{ $point['bullet'] }}</li>
              @endforeach
            </ul>
          </div>
          @endif
        </div>
      </article>
      @endforeach
    </section>
    @endif

    <!-- Education -->
    @if(!empty($resumeData['education']))
    <section class="education">
      <h2 class="section-title">Education</h2>
      @foreach($resumeData['education'] as $edu)
      <article class="edu">
        <div class="edu-header">
          <div class="years">{{ $edu['educationDates']['start']['date'] ?? '' }} — {{ $edu['educationDates']['end']['date'] ?? '' }}</div>
          <div class="degree">{{ $edu['educationLevel']['label'] ?? '' }}</div>
          <div class="school">{{ $edu['educationOrganization'] ?? '' }}</div>
        </div>
      </article>
      @endforeach
    </section>
    @endif

    <!-- Languages -->
    @if(!empty($resumeData['languages']))
    <section class="languages">
      <h2 class="section-title">Languages</h2>
      <ul class="job">
        @foreach($resumeData['languages'] as $lang)
          <li>{{ $lang['name'] }} ({{ $lang['level'] ?? 'Fluent' }})</li>
        @endforeach
      </ul>
    </section>
    @endif

    <footer class="footer">
      <p>Generated Resume — Last updated: {{ now()->format('M d, Y') }}</p>
    </footer>
  </main>
</body>
</html>
