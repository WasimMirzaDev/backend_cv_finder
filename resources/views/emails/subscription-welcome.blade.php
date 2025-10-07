<!DOCTYPE html>
<html>
<head>
    <title>Welcome to {{ config('app.name') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 1px solid #eee;
            margin-bottom: 20px;
        }
        .content {
            padding: 20px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            font-size: 0.9em;
            color: #777;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            margin: 20px 0;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Welcome to {{ config('app.name') }}!</h1>
    </div>
    
    <div class="content">
        <p>Hello {{ $user->name }},</p>
        
        <p>Thank you for subscribing to our <strong>{{ $plan->title }}</strong> plan. We're excited to have you on board!</p>
        
        <p>Your subscription is now active, and you can start using all the premium features immediately.</p>
        
        <p>Here are your subscription details:</p>
        <ul>
            <li><strong>Plan:</strong> {{ $plan->title }}</li>
            <li><strong>Billing Cycle:</strong> {{ ucfirst($plan->interval) }}ly</li>
            <li><strong>Subscription Start:</strong> {{ now()->format('F j, Y') }}</li>
            @if($plan->interval === 'year')
            <li><strong>Next Billing Date:</strong> {{ now()->addYear()->format('F j, Y') }}</li>
            @else
            <li><strong>Next Billing Date:</strong> {{ now()->addMonth()->format('F j, Y') }}</li>
            @endif
        </ul>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ url('/dashboard') }}" class="button">Go to Dashboard</a>
        </div>
        
        <p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>
        
        <p>Best regards,<br>The {{ config('app.name') }} Team</p>
    </div>
    
    <div class="footer">
        <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        <p>
            <a href="{{ url('/privacy') }}" style="color: #4CAF50; text-decoration: none;">Privacy Policy</a> | 
            <a href="{{ url('/terms') }}" style="color: #4CAF50; text-decoration: none;">Terms of Service</a>
        </p>
    </div>
</body>
</html>
