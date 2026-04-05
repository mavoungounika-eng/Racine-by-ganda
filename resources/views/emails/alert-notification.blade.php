<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .alert-container {
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .alert-critical {
            border-color: #dc3545;
            background-color: #f8d7da;
        }
        .alert-high {
            border-color: #fd7e14;
            background-color: #ffe5d0;
        }
        .alert-warning {
            border-color: #ffc107;
            background-color: #fff3cd;
        }
        .alert-info {
            border-color: #17a2b8;
            background-color: #d1ecf1;
        }
        .alert-header {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .alert-message {
            font-size: 16px;
            margin: 15px 0;
        }
        .alert-meta {
            font-size: 14px;
            color: #666;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
        }
        .context-table {
            width: 100%;
            margin-top: 15px;
            border-collapse: collapse;
        }
        .context-table th,
        .context-table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .context-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="alert-container alert-{{ $alert['severity'] }}">
        <div class="alert-header">
            @if($alert['severity'] === 'critical')
                🚨
            @elseif($alert['severity'] === 'high')
                ⚠️
            @elseif($alert['severity'] === 'warning')
                🔶
            @else
                ℹ️
            @endif
            {{ $alert['title'] }}
        </div>

        <div class="alert-message">
            {{ $alert['message'] }}
        </div>

        @if(!empty($alert['context']))
        <table class="context-table">
            <thead>
                <tr>
                    <th>Key</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                @foreach($alert['context'] as $key => $value)
                <tr>
                    <td><strong>{{ $key }}</strong></td>
                    <td>{{ is_scalar($value) ? $value : json_encode($value) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <div class="alert-meta">
            <strong>Severity:</strong> {{ strtoupper($alert['severity']) }}<br>
            <strong>Environment:</strong> {{ $alert['environment'] }}<br>
            <strong>Timestamp:</strong> {{ $alert['timestamp'] }}
        </div>
    </div>

    <div class="footer">
        <p>
            <strong>{{ config('app.company.name') }}</strong><br>
            Monitoring & Alerts System<br>
            This is an automated alert. Please do not reply to this email.
        </p>
    </div>
</body>
</html>
