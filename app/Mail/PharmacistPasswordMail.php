<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PharmacistPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $password;

    public function __construct(string $password)
    {
        $this->password = $password;
    }

    public function build()
    {
        return $this->subject('Your Account Password')
                    ->html("<p>Welcome! Your email has been verified.</p><p>Your login password is: <strong>{$this->password}</strong></p>");
    }
}
