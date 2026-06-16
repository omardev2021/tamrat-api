<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WinBack extends Mailable
{
    use Queueable, SerializesModels;

    public $firstName;
    public $code;

    public function __construct($firstName, $code = '')
    {
        $this->firstName = $firstName;
        $this->code = $code;
    }

    public function build()
    {
        return $this->subject('اشتقنا لك 🌿 | تمرات')
            ->view('emails.winback');
    }
}
