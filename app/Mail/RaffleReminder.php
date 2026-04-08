<?php

namespace App\Mail;

use App\Models\Raffle;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RaffleReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Raffle $raffle,
        public readonly string $participantName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Recordatorio: El sorteo {$this->raffle->name} cierra mañana",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.raffle-reminder',
            with: [
                'raffle' => $this->raffle,
                'participantName' => $this->participantName,
            ],
        );
    }
}
