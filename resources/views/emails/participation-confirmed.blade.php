<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2>¡Tu participación ha sido confirmada!</h2>
    <p>Hola {{ $participant->name }},</p>
    <p>Tu participación en el sorteo <strong>{{ $raffle->name }}</strong> ha sido registrada exitosamente.</p>
    <p>El sorteo cierra el: <strong>{{ \Carbon\Carbon::parse($raffle->ends_at)->format('d/m/Y H:i') }}</strong></p>
    <p>¡Mucha suerte!</p>
</body>
</html>
