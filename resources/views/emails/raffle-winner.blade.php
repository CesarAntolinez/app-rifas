<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2 style="color: #16a34a;">🎉 ¡Felicidades, {{ $participantName }}!</h2>
    <p>Has ganado en el sorteo <strong>{{ $raffle->name }}</strong>.</p>
    @if (!empty($prizes))
        <h3>Tus premios:</h3>
        <ul>
            @foreach ($prizes as $prize)
                <li><strong>{{ $prize['prize_name'] }}</strong> — Serie {{ $prize['serie'] }} / Boleto #{{ $prize['number'] }}</li>
            @endforeach
        </ul>
    @endif
    <p>El organizador se pondrá en contacto contigo para coordinar la entrega de tu(s) premio(s).</p>
</body>
</html>
