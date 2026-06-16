<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AbandonedCart extends Mailable
{
    use Queueable, SerializesModels;

    public $firstName;
    public $items;
    public $resumeUrl;

    public function __construct($firstName, $items, $resumeUrl)
    {
        $this->firstName = $firstName;
        $this->items = $items;
        $this->resumeUrl = $resumeUrl;
    }

    public function build()
    {
        return $this->subject('سلة التمر بانتظارك 🛒 | تمرات')
            ->view('emails.abandoned-cart');
    }
}
