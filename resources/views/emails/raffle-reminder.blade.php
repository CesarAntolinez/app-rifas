<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2>Recordatorio: El sorteo cierra mañana</h2>
    <p>Hola {{ $participantName }},</p>
    <p>Te recordamos que el sorteo <strong>{{ $raffle->name }}</strong> cierra mañana el <strong>{{ \Carbon\Carbon::parse($raffle->ends_at)->format('d/m/Y H:i') }}</strong>.</p>
    <p>¡Mucha suerte!</p>
</body>
</html>
