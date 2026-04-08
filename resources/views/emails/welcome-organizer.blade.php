<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2>Bienvenido a Rifas, {{ $user->name }}</h2>
    <p>Tu cuenta de organizador ha sido creada. Aquí están tus credenciales de acceso:</p>
    <table style="background: #f9fafb; padding: 16px; border-radius: 8px; width: 100%;">
        <tr><td><strong>Email:</strong></td><td>{{ $user->email }}</td></tr>
        <tr><td><strong>Contraseña temporal:</strong></td><td>{{ $temporaryPassword }}</td></tr>
    </table>
    <p style="color: #dc2626; margin-top: 16px;"><strong>Importante:</strong> Deberás cambiar tu contraseña al iniciar sesión por primera vez.</p>
    <p>Puedes ingresar en: <a href="{{ config('app.url') }}/login">{{ config('app.url') }}/login</a></p>
</body>
</html>
