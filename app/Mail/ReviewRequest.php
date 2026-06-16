<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReviewRequest extends Mailable
{
    use Queueable, SerializesModels;

    public $firstName;
    public $reviewUrl;

    public function __construct($firstName, $reviewUrl)
    {
        $this->firstName = $firstName;
        $this->reviewUrl = $reviewUrl;
    }

    public function build()
    {
        return $this->subject('كيف كانت تمورك؟ 🌟 شاركنا رأيك | تمرات')
            ->view('emails.review-request');
    }
}
