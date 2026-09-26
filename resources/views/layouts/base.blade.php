<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Agriconnect')</title>
    <meta name="description" content="Agriconnect met en relation producteurs et acheteurs pour écouler les denrées périssables à Lomé, avec un prix estimé par régression linéaire.">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600;700&family=Fraunces:opsz,wght@9..144,560;9..144,680&family=Outfit:wght@380;480;580;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@700;800&text=VA%C6%91LE&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/agriconnect.css') }}">
</head>
<body class="@yield('body-class', 'theme-paper')">
    @yield('content')
    <script src="{{ asset('js/agriconnect.js') }}"></script>
</body>
</html>
