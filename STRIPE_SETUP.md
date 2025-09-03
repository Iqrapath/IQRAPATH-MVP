# Stripe Payment Integration Setup

## Environment Variables

Add these variables to your `.env` file:

```env
# Stripe Configuration
STRIPE_PUBLISHABLE_KEY=pk_test_your_publishable_key_here
STRIPE_SECRET_KEY=sk_test_your_secret_key_here
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret_here
```

## Getting Stripe Keys

1. **Sign up for Stripe**: Go to [https://stripe.com](https://stripe.com) and create an account
2. **Get Test Keys**: 
   - Go to your Stripe Dashboard
   - Click on "Developers" → "API keys"
   - Copy the "Publishable key" and "Secret key" from the "Test mode" section
3. **Set up Webhooks** (optional for now):
   - Go to "Developers" → "Webhooks"
   - Add endpoint: `https://yourdomain.com/stripe/webhook`
   - Select events: `payment_intent.succeeded`, `payment_intent.payment_failed`
   - Copy the webhook secret

## Test Cards

Use these test card numbers for testing:

### Successful Payments
- **Visa**: 4242 4242 4242 4242
- **Mastercard**: 5555 5555 5555 4444
- **American Express**: 3782 822463 10005

### Declined Payments
- **Generic Decline**: 4000 0000 0000 0002
- **Insufficient Funds**: 4000 0000 0000 9995
- **Expired Card**: 4000 0000 0000 0069
- **Incorrect CVC**: 4000 0000 0000 0127

### Test Details
- **Expiry Date**: Any future date (e.g., 12/25)
- **CVC**: Any 3-digit number (e.g., 123)
- **ZIP Code**: Any valid ZIP code

## Security Notes

- **Never commit real API keys** to version control
- **Use test keys** during development
- **Switch to live keys** only in production
- **Keep secret keys secure** and never expose them in frontend code

## Next Steps

1. Add the environment variables to your `.env` file
2. Test the payment flow with test cards
3. Set up webhooks for production
4. Switch to live keys when ready for production
