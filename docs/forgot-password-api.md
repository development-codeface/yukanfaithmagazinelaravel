# Forgot password API

Base URL: `http://127.0.0.1:8000/api`. All endpoints are public. Send `Accept: application/json` and `Content-Type: application/json`. Delivery uses the configured application mailer.

## 1. Send or resend email OTP

`POST /forgot-password/send-otp`

```json
{"email":"customer@example.com"}
```

200 response (also returned for an unknown email):

```json
{"message":"If an account exists for this email, a password reset code has been sent.","expires_in":600}
```

The email contains a six-digit code, valid for 10 minutes. Wait 60 seconds between sends to the same email; the endpoint also limits requests to 5 per minute per IP. Resending replaces the previous code and any verified reset token. Codes are stored only as hashes and never returned by the API. Unknown and soft-deleted accounts receive no email.

## 2. Verify OTP

`POST /forgot-password/verify-otp`

```json
{"email":"customer@example.com","otp":"123456"}
```

200 response:

```json
{"message":"Code verified. You can now set a new password.","reset_token":"<64-character token>","expires_in":600}
```

Send the OTP as a string. After five incorrect attempts, request a new code. Verification consumes the code and returns a separate, single-use reset token valid for 10 minutes. Keep this token temporarily in app memory and pass it to step 3. The response has `Cache-Control: no-store`. This token cannot authenticate normal API requests.

## 3. Create new password

`POST /forgot-password/reset-password`

```json
{
  "email":"customer@example.com",
  "reset_token":"<token from step 2>",
  "password":"NewPassword2!",
  "password_confirmation":"NewPassword2!"
}
```

200 response:

```json
{"message":"Password reset successfully. Please log in with your new password."}
```

Passwords require at least 8 characters, uppercase and lowercase letters, a number, a symbol, and matching confirmation, consistent with registration. Success consumes the reset token, revokes all API login tokens, rotates the remember-me token, and removes database-backed web sessions when configured. Return the customer to Login; resetting a password does not automatically log them in. Other web session drivers retain their existing session behavior.

Verification and reset each allow 20 requests per minute per IP. Validation failures, expired codes/tokens and reuse return 422 with an `errors` object. Throttling returns 429; respect `Retry-After` (email cooldown also returns `retry_after` seconds). Email delivery failures return 503 without transport details.

## App flow

Login → Forgot password (email) → Verify code (OTP and resend action) → New password and confirmation → Login.

## Deployment

Run `php artisan migrate`. Configure a working SMTP or supported production mailer and sender in the environment. With `MAIL_MAILER=log`, emails go to the configured application log instead of an inbox; `array` keeps messages in memory. Sending is synchronous, so no queue worker is required. Feature tests send no real emails.

### SMTP configuration (web reset links and API OTP)

Edit the project's root `.env` file, replacing its existing `MAIL_*` settings with your provider's values. Do not put credentials in controllers, views, or committed files.

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp.your-provider.com
MAIL_PORT=587
MAIL_USERNAME=your-smtp-username
MAIL_PASSWORD="your-smtp-password"
MAIL_FROM_ADDRESS="noreply@your-domain.com"
MAIL_FROM_NAME="Yukan Faith Magazine"
```

This example uses SMTP with STARTTLS on port 587. If your provider requires implicit TLS on port 465, use `MAIL_SCHEME=smtps` and `MAIL_PORT=465`. Follow your provider's host, port and authentication settings. `MAIL_URL`, if configured, must also match these settings. This project's mail configuration reads `MAIL_SCHEME` rather than `MAIL_ENCRYPTION`.

Set `APP_URL` to your real public site URL on the server so emailed reset links point to the right domain. Locally, use `http://127.0.0.1:8000`. After editing `.env`, run `php artisan config:clear` (or rebuild the deployment's configuration cache using `php artisan config:cache`).

The web Login page includes a Forgot password link to `/password/reset`. It sends an email reset link; the mobile/API flow sends an OTP. Both use the same SMTP configuration.
