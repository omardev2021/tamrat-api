<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderShipped extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $items;
    public $trackingUrl;

    public function __construct(Order $order, $items, ?string $trackingUrl = null)
    {
        $this->order = $order;
        $this->items = $items;
        $this->trackingUrl = $trackingUrl;
    }

    public function build()
    {
        return $this->subject('طلبك في الطريق إليك 🚚 #' . $this->order->id)
            ->view('emails.order-shipped');
    }
}
