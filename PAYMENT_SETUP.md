# Payment Integration Setup Guide

This guide explains how to set up real payment processing for the UniConnect platform using Chapa (Ethiopian Payments) and Stripe (International).

## Chapa Setup (Recommended for Ethiopia)

### 1. Create Chapa Account
1. Visit https://dashboard.chapa.co/
2. Sign up for a merchant account
3. Complete verification process

### 2. Get API Keys
1. Go to Settings > API Keys in your Chapa dashboard
2. Copy your Secret Key and Public Key

### 3. Configure Environment Variables
Add these to your `.env` file or server environment:

```bash
CHAPA_SECRET_KEY=your_actual_chapa_secret_key_here
CHAPA_PUBLIC_KEY=your_actual_chapa_public_key_here
UNICONNECT_WEBHOOK_SECRET=your_secure_webhook_secret
```

Or update `config/payments.php` directly (not recommended for production).

### 4. Configure Webhook URL
1. In Chapa dashboard, go to Settings > Webhooks
2. Add webhook URL: `https://yourdomain.com/views/api/payment_webhook.php`
3. Select events: `payment.success`, `payment.failed`

### 5. For Local Development
Since webhooks require public URLs, use ngrok:
```bash
npm install -g ngrok
ngrok http 80
# Copy the https URL and use it as your webhook URL in Chapa
```

### 6. Test Payments
Chapa provides test cards for development:
- Test Card: `4111 1111 1111 1111`
- Expiry: Any future date
- CVV: Any 3 digits

## Stripe Setup (International)

### 1. Create Stripe Account
1. Visit https://dashboard.stripe.com/
2. Complete account setup

### 2. Get API Keys
1. Go to Developers > API Keys
2. Copy your Secret Key and Public Key

### 3. Configure Webhook
1. Go to Developers > Webhooks
2. Add endpoint: `https://yourdomain.com/views/api/payment_webhook.php`
3. Select events: `payment_intent.succeeded`, `payment_intent.payment_failed`

### 4. Environment Variables
```bash
STRIPE_SECRET_KEY=sk_live_...
STRIPE_PUBLIC_KEY=pk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

## Security Notes

- Never commit API keys to version control
- Use environment variables in production
- Regularly rotate webhook secrets
- Validate webhook signatures to prevent fraud

## Testing

The system automatically detects test vs production keys:
- If keys contain "TEST" or are placeholder values, it runs in demo mode
- Demo mode simulates successful payments without real charges
- Production mode makes real API calls to payment providers

## Troubleshooting

### Webhook Issues
- Ensure webhook URL is publicly accessible
- Check webhook signature validation
- Monitor server logs for webhook processing

### Payment Failures
- Verify API keys are correct
- Check payment provider dashboard for transaction details
- Ensure webhook endpoint responds with 200 status

### Local Development
- Use ngrok or similar tunneling service
- Update webhook URLs in payment provider dashboards
- Test with small amounts first