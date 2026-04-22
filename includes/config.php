<?php

return [
    'whatsapp' => [
        // Supported drivers: twilio or none
        // On XAMPP localhost, environment variables are often not loaded by default.
        // So you can set real values directly below and they will be used immediately.
        'driver' => getenv('WHATSAPP_DRIVER') ?: 'twilio',

        // Twilio credentials and sender details
        'twilio' => [
            // Add your real Twilio values here for XAMPP localhost if env vars
            // are not available yet.
            'account_sid' => getenv('TWILIO_ACCOUNT_SID') ?: 'YOUR_TWILIO_SID',
            'auth_token' => getenv('TWILIO_AUTH_TOKEN') ?: 'YOUR_TWILIO_AUTH_TOKEN',
            'from_number' => getenv('TWILIO_WHATSAPP_FROM') ?: 'whatsapp:+14155238886',
        ],

        // Default country code used when the phone number is saved without one.
        'default_country_code' => getenv('WHATSAPP_DEFAULT_COUNTRY_CODE') ?: '+91',

        // Log file for WhatsApp API errors and delivery attempts.
        'log_file' => __DIR__ . '/../logs/whatsapp.log',
    ],
    'openai' => [
        'api_key' => getenv('OPENAI_API_KEY') ?: 'YOUR_OPENAI_API_KEY',
        'model' => getenv('OPENAI_MODEL') ?: 'gpt-4o-mini',
        'endpoint' => getenv('OPENAI_ENDPOINT') ?: 'https://api.openai.com/v1/chat/completions',
        'timeout' => 20,
    ],
];
