<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>S2Mangás</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            color: #333333;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #e63946;
            text-align: center;
        }

        p {
            font-size: 16px;
            line-height: 1.5;
            margin-bottom: 20px;
        }

        .code {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            background-color: #f1f1f1;
            padding: 10px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .footer {
            text-align: center;
            font-size: 14px;
            color: #888888;
            margin-top: 40px;
        }
    </style>
</head>

<body>

    <div class="container">
        <h1>S2Mangás</h1>

        <p>Prezado(a) {{$user->name}},</p>

        <p>Para recuperar a sua senha do app S2Mangás, use o código de verificação abaixo:</p>

        <div class="code">{{ $code }}</div>

        <p>Por questões de segurança, esse código é válido somente até as {{ $formattedTime }} do dia {{ $formattedDate }}. Caso o prazo esteja expirado, será necessário solicitar outro código.</p>

        <p>Atenciosamente,</p>

        <p>Suporte S2Mangás</p>

        <div class="footer">
            <p>&copy; 2024 S2Mangás. Todos os direitos reservados.</p>
        </div>
    </div>

</body>

</html>
