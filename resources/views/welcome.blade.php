<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ env('APP_NAME') }}</title>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Ubuntu&display=swap');

        body {
            font-family: 'Ubuntu', sans-serif;
            background-color: #f5f6fa
        }

        .container {
            height: 100vh;
            width: 100%;
            display: flex;
            justify-content: center;
            flex-direction: column;
            align-items: center;
        }

        .container h1 {
            font-size: 50px;
            color: #0F6987
        }

        .container h4 {
            font-size: 35px;
            color: #ffcc00
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>HACKATON MOMO API 2022</h1>
        <h4>{{ env('APP_NAME') }}</h4>
    </div>
</body>

</html>
