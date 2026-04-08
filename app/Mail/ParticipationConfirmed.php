<?php

namespace App\Mail;

use App\Models\Participant;
use App\Models\Raffle;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ParticipationConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Participant $participant,
        public readonly Raffle $raffle,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Tu participación en {$this->raffle->name} ha sido confirmada",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.participation-confirmed',
            with: [
                'participant' => $this->participant,
                'raffle' => $this->raffle,
            ],
        );
    }
}
