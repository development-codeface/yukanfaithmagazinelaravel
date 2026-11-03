<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class PasswordResetOtp extends Mailable
{
    public function __construct(public string $otp) {}

    public function build(): self
    {
        return $this->subject('Your password reset code')
            ->text('emails.password-reset-otp');
    }
}
