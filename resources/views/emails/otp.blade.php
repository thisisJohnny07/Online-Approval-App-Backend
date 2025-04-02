<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification - Online Approval App</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 20;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .header {
            background-color: #313477;
            color: #ffffff;
            padding: 15px;
            font-size: 20px;
            font-weight: bold;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }
        .otp {
            font-size: 24px;
            font-weight: bold;
            color: #313477;
            margin: 20px 0;
        }
        .message {
            font-size: 16px;
            color: #333333;
            margin-bottom: 20px;
        }
        .footer {
            font-size: 14px;
            color: #666666;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">Online Approval App</div>
        <p class="message">Dear User,</p>
        <p class="message">Your One-Time Password (OTP) for verification is:</p>
        <p class="otp">{{ $otp }}</p>
        <p class="message">This OTP is valid for a limited time. Please do not share it with anyone.</p>
        <p class="footer">If you did not request this code, please ignore this email or contact support.</p>
    </div>
</body>
</html>