<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title ?? config("app.name") }} | Promodruk</title>

        <link rel="stylesheet" href="{{ asset("css/app.css") }}">
    </head>
    <body>
        @yield("content")
    </body>

    <script src="{{ asset("js/app.js") }}"></script>
</html>
