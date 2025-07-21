{{-- filepath: backend/resources/views/emails/verification_code.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Infinitrix Cargo Express - Email Verification Code</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f9f9f9; color: #222;">
    <div style="max-width: 480px; margin: 40px auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); padding: 32px;">
        <h1 style="text-align: center; color: #22c55e; margin-bottom: 24px;">Welcome to Infinitrix Cargo Express!</h1>
        <p style="font-size: 16px;">Hello{{ isset($name) ? ' ' . $name : '' }},</p>
        <p style="font-size: 15px; margin-bottom: 24px;">
            Thank you for choosing Infinitrix Cargo Express. To complete your registration and secure your account, please enter the verification code below on our website.
        </p>
        <div style="text-align: center; margin: 32px 0;">
            <span style="display: inline-block; font-size: 2.5rem; letter-spacing: 0.5em; color: #22c55e; font-weight: bold; background: #f3f4f6; padding: 16px 32px; border-radius: 8px;">
                {{ $code }}
            </span>
        </div>
        <p style="font-size: 15px;">
            <strong>Note:</strong> This code will expire in 10 minutes for your security.
        </p>
        <p style="font-size: 15px; margin-top: 24px;">
            If you did not create an account with us, please disregard this email.
        </p>
        <hr style="margin: 32px 0;">
        <p style="font-size: 14px; color: #888; text-align: center;">
            Thank you for trusting Infinitrix Cargo Express.<br>
            <strong style="color: #22c55e;">Infinitrix Cargo Express Team</strong>
        </p>
    </div>
</body>
</html>