<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderStatusMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public string $type = 'created'
    ) {
    }

    public function build()
    {
        $subject = match ($this->type) {
            'paid' => 'Đơn hàng #' . $this->order->code . ' đã thanh toán thành công',
            'confirmed' => 'Đơn hàng #' . $this->order->code . ' đã được xác nhận',
            'shipping' => 'Đơn hàng #' . $this->order->code . ' đang được giao',
            'completed' => 'Đơn hàng #' . $this->order->code . ' đã giao thành công',
            'cancelled' => 'Đơn hàng #' . $this->order->code . ' đã bị hủy',
            default => 'Xác nhận đơn hàng #' . $this->order->code,
        };

        return $this->subject($subject)
            ->view('emails.orders.status');
    }
}