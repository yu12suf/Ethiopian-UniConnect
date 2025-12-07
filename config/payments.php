<?php
// Payments config for production use with Chapa and Stripe
// To enable real payments:
// 1. Sign up at https://dashboard.chapa.co/
// 2. Get your API keys from the dashboard
// 3. Set environment variables or replace the values below
// 4. Configure webhook URL in Chapa dashboard: https://yourdomain.com/views/api/payment_webhook.php
// 5. For local development, use ngrok or similar to expose webhook endpoint

return [
    'webhook_secret' => getenv('UNICONNECT_WEBHOOK_SECRET') ?: 'your-webhook-secret-here',
    'chapa' => [
        'secret_key' => getenv('CHAPA_SECRET_KEY') ?: 'CHAPA_SECRET_KEY_HERE', // Replace with your real Chapa secret key
        'public_key' => getenv('CHAPA_PUBLIC_KEY') ?: 'CHAPA_PUBLIC_KEY_HERE', // Replace with your real Chapa public key
        'webhook_url' => site_url('views/api/payment_webhook.php'),
    ],
    'stripe' => [
        'secret_key' => getenv('STRIPE_SECRET_KEY') ?: 'sk_test_...', // Replace with real Stripe secret key
        'public_key' => getenv('STRIPE_PUBLIC_KEY') ?: 'pk_test_...', // Replace with real Stripe public key
        'webhook_secret' => getenv('STRIPE_WEBHOOK_SECRET') ?: 'whsec_...', // Replace with real Stripe webhook secret
    ],
    'default_gateway' => 'chapa', // or 'stripe'
];
