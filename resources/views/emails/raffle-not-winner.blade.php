<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2>Resultados del sorteo {{ $raffle->name }}</h2>
    <p>Hola {{ $participantName }},</p>
    <p>El sorteo <strong>{{ $raffle->name }}</strong> ha sido ejecutado. En esta ocasión tu boleto no resultó ganador.</p>
    <p>Gracias por tu participación. ¡Sigue intentándolo en futuros sorteos!</p>
</body>
</html>
