<?php

namespace App\Services;

use App\Models\Participant;
use App\Models\Raffle;
use App\Models\Ticket;
use InvalidArgumentException;

class TicketService
{
    public function generateTicket(Raffle $raffle, Participant $participant, string $serie, string $number): Ticket
    {
        $range = $this->getNumberRange($raffle->ticket_digit_type);
        $numericValue = (int) $number;

        if ($numericValue < $range['min'] || $numericValue > $range['max']) {
            throw new InvalidArgumentException(
                "El número {$number} está fuera del rango válido ({$range['min']}-{$range['max']}) para el tipo {$raffle->ticket_digit_type}."
            );
        }

        if (! $this->validateUniqueness($raffle->id, $serie, $number)) {
            throw new InvalidArgumentException(
                "El número {$number} de la serie {$serie} ya está asignado en este sorteo."
            );
        }

        return Ticket::create([
            'raffle_id' => $raffle->id,
            'participant_id' => $participant->id,
            'serie' => $serie,
            'number' => $number,
            'is_winner' => false,
        ]);
    }

    public function validateUniqueness(int $raffleId, string $serie, string $number): bool
    {
        return ! Ticket::where('raffle_id', $raffleId)
            ->where('serie', $serie)
            ->where('number', $number)
            ->exists();
    }

    public function getAvailableNumbers(Raffle $raffle, string $serie): array
    {
        $range = $this->getNumberRange($raffle->ticket_digit_type);
        $taken = Ticket::where('raffle_id', $raffle->id)
            ->where('serie', $serie)
            ->pluck('number')
            ->toArray();

        $digits = match ($raffle->ticket_digit_type) {
            'double' => 2,
            'triple' => 3,
            'quadruple' => 4,
        };

        $available = [];
        for ($i = $range['min']; $i <= $range['max']; $i++) {
            $formatted = str_pad((string) $i, $digits, '0', STR_PAD_LEFT);
            if (! in_array($formatted, $taken)) {
                $available[] = $formatted;
            }
        }

        return $available;
    }

    public function getNumberRange(string $digitType): array
    {
        return match ($digitType) {
            'double' => ['min' => 0, 'max' => 99],
            'triple' => ['min' => 0, 'max' => 999],
            'quadruple' => ['min' => 0, 'max' => 9999],
            default => throw new InvalidArgumentException("Tipo de dígito inválido: {$digitType}"),
        };
    }
}
