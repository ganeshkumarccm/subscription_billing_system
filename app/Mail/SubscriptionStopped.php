<?php

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionStopped extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Subscription $subscription)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your subscription has been stopped',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.subscription-stopped',
        );
    }
}
