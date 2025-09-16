<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Chenesa') }} - Smart Water Management</title>

        <meta name="description" content="Smart water management solutions for Zimbabwe and South Africa. Monitor your water tanks in real-time, predict usage patterns, and automatically reorder water before you run out.">
        <meta name="keywords" content="water management, Zimbabwe, South Africa, IoT sensors, water monitoring, tank monitoring">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Favicon -->
        <link rel="icon" type="image/x-icon" href="/favicon.ico">

        <!-- Scripts -->
        @vite(['resources/js/landing-app.jsx'])
    </head>
    <body class="antialiased">
        <div id="landing-app"></div>
    </body>
</html>