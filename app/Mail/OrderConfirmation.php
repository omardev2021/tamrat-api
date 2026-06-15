<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $items;

    public function __construct(Order $order, $items)
    {
        $this->order = $order;
        $this->items = $items;
    }

    public function build()
    {
        return $this->subject('تأكيد طلبك من تمرات #' . $this->order->id)
            ->view('emails.order-confirmation');
    }
}
