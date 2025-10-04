<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>{{ $resumeData['candidateName'][0]['firstName'] ?? '' }} {{ $resumeData['candidateName'][0]['familyName'] ?? '' }} — Resume</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* use your existing styles here (kept intact) */
  </style>
</head>
<body>
  <main class="resume" role="main">
    <header class="header">
      <div class="brand-mark" aria-hidden="true"></div>
      <div class="title-block">
        <h1 class="name">{{ $resumeData['candidateName'][0]['firstName'] ?? '' }} {{ $resumeData['candidateName'][0]['familyName'] ?? '' }}</h1>
      </div>
    </header>

    <!-- Personal Details -->
    <section class="personal">
      <h2 class="section-title">Personal Details</h2>
      <div class="personal-grid">
        <div class="field"><div class="label">Name</div><div class="value">{{ $resumeData['candidateName'][0]['firstName'] ?? '' }} {{ $resumeData['candidateName'][0]['familyName'] ?? '' }}</div></div>
        <div class="field"><div class="label">Email</div><div class="value"><a href="mailto:{{ $resumeData['email'][0] ?? '' }}">{{ $resumeData['email'][0] ?? '' }}</a></div></div>
        <div class="field"><div class="label">Phone</div><div class="value"><a href="tel:{{ $resumeData['phoneNumber'][0]['formattedNumber'] ?? '' }}">{{ $resumeData['phoneNumber'][0]['formattedNumber'] ?? '' }}</a></div></div>
        <div class="field"><div class="label">Address</div><div class="value">{{ $resumeData['location']['formatted'] ?? '' }}</div></div>
        <div class="field"><div class="label">City</div><div class="value">{{ $resumeData['location']['city'] ?? '' }}</div></div>
      </div>
    </section>

    <!-- Summary -->
    <section class="summary">
      <h2 class="section-title">Summary</h2>
      <p>{{ $resumeData['summary']['paragraph'] ?? '' }}</p>
    </section>

    <!-- Employment -->
    @if(!empty($resumeData['workExperience']))
    <section class="experience">
      <h2 class="section-title">Employment</h2>
      @foreach($resumeData['workExperience'] as $job)
      <article class="job">
        <header class="job-header">
          <div class="job-meta">
            <div class="daterange">{{ $job['workExperienceDates']['start']['date'] ?? '' }} — {{ $job['workExperienceDates']['end']['date'] ?? 'Present' }}</div>
            <div class="role">{{ $job['workExperienceJobTitle'] ?? '' }}</div>
            <div class="company">{{ $job['workExperienceOrganization'] ?? '' }}</div>
          </div>
        </header>
        <div class="job-body">
          <p>{{ $job['workExperienceDescription'] ?? '' }}</p>
          @if(!empty($job['highlights']['items']))
          <div class="subgrid">
            <div>
              <h3>Key Achievements</h3>
              <ul>
                @foreach($job['highlights']['items'] as $point)
                  <li>{{ $point['bullet'] }}</li>
                @endforeach
              </ul>
            </div>
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

    <footer class="footer">
      <p>
        This resume is print-ready. Export as PDF (A4). Last updated: <span>{{ now()->format('M d, Y') }}</span>
      </p>
    </footer>
  </main>
</body>
</html>
