<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensaje de Contacto</title>
</head>
<body>
    <div class="container">
        <div class="header">
            Nuevo Mensaje de Contacto
        </div>
        <div class="content">
            <p>Has recibido un nuevo mensaje a través del formulario de contacto de Picxury.</p>
            <hr>
            <p><strong>Nombre:</strong> {{ $name }}</p>
            <p><strong>Email:</strong> {{ $email }}</p>
            
            <div class="message-box">
                <p><strong>Mensaje:</strong></p>
                <p>{{ $messageContent }}</p>
            </div>
        </div>
    </div>
</body>
</html>