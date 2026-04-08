<?php

namespace App\Mail;

use App\Models\Raffle;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RaffleWinner extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Raffle $raffle,
        public readonly string $participantName,
        public readonly array $prizes = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "¡Felicidades! Ganaste en {$this->raffle->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.raffle-winner',
            with: [
                'raffle' => $this->raffle,
                'participantName' => $this->participantName,
                'prizes' => $this->prizes,
            ],
        );
    }
}
