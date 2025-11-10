<?php
// Optional SMTP/PHPMailer configuration.
// Copy this file to enable SMTP and configure values.
return [
    // Set to true to enable PHPMailer SMTP usage
    'use_smtp' => false,

    // SMTP settings (used when use_smtp = true)
    'host' => 'smtp.example.com',
    'username' => 'user@example.com',
    'password' => 'yourpassword',
    'port' => 587,
    'encryption' => 'tls', // tls or ssl
    'from_email' => 'no-reply@uniconnect.local',
    'from_name' => 'UniConnect',

    // Optional: set to true to verify server certificates (default true)
    'smtp_verify' => true,
];
