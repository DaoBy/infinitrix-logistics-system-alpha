<!DOCTYPE html>
<html>
<head>
    <title>New Contact Form Submission</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #16a34a; color: white; padding: 20px; text-align: center; }
        .content { background: #f9f9f9; padding: 20px; }
        .field { margin-bottom: 15px; }
        .field-label { font-weight: bold; color: #16a34a; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>New Contact Form Submission</h1>
        </div>
        <div class="content">
            <div class="field">
                <span class="field-label">Name:</span>
                <span>{{ $data['name'] }}</span>
            </div>
            <div class="field">
                <span class="field-label">Email:</span>
                <span>{{ $data['email'] }}</span>
            </div>
            <div class="field">
                <span class="field-label">Subject:</span>
                <span>{{ $data['subject'] }}</span>
            </div>
            <div class="field">
                <span class="field-label">Message:</span>
                <p>{{ $data['message'] }}</p>
            </div>
            <div class="field">
                <span class="field-label">Submitted At:</span>
                <span>{{ now()->format('F j, Y \a\t g:i A') }}</span>
            </div>
        </div>
    </div>
</body>
</html>