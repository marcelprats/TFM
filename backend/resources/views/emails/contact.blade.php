<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body>
    <h2>Nou missatge de contacte</h2>
    <p><strong>Nom:</strong> {{ $data['name'] }}</p>
    <p><strong>Email:</strong> {{ $data['email'] }}</p>
    <p><strong>Assumpte:</strong> {{ $data['subject'] }}</p>
    <p><strong>Missatge:</strong><br>{{ nl2br(e($data['message'])) }}</p>
</body>
</html>
