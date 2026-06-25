<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReorderReminder extends Mailable
{
    use Queueable, SerializesModels;

    public $firstName;
    public $lastProduct;

    public function __construct($firstName, $lastProduct)
    {
        $this->firstName = $firstName;
        $this->lastProduct = $lastProduct;
    }

    public function build()
    {
        return $this->subject('لحظةٌ تستحقّ أن تتكرّر 🌴 | تمرات')
            ->view('emails.reorder-reminder');
    }
}
