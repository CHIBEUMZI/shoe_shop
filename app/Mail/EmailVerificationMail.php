<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailVerificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $email,
        public string $code,
        public string $token
    ) {
    }

    public function build()
    {
        return $this
            ->subject('Xác nhận email đăng ký - BMC Shoes')
            ->view('emails.auth.verification');
    }
}
