<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Booking $booking
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Booking Confirmed — ' . $this->booking->booking_reference)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your booking has been confirmed successfully.')
            ->line('**Booking Reference:** ' . $this->booking->booking_reference)
            ->line('**Event:** ' . $this->booking->ticket->event->title)
            ->line('**Ticket Type:** ' . $this->booking->ticket->type->value)
            ->line('**Quantity:** ' . $this->booking->quantity)
            ->line('**Total Paid:** IDR ' . number_format($this->booking->total_amount))
            ->action('View Booking', url('/bookings/' . $this->booking->id))
            ->line('Thank you for your purchase!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'booking_id'        => $this->booking->id,
            'booking_reference' => $this->booking->booking_reference,
            'event'             => $this->booking->ticket->event->title,
            'total_amount'      => $this->booking->total_amount,
            'message'           => 'Your booking ' . $this->booking->booking_reference . ' has been confirmed.',
        ];
    }
}
