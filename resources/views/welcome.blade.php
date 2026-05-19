<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Fuganda') }} — Uganda Property Listings</title>
        <meta name="description" content="Discover properties for rent and sale across Uganda with quick map search.">
        <meta name="robots" content="index,follow">

        <!-- Open Graph defaults (overridden per-page by usePageMeta composable) -->
        <meta property="og:site_name" content="{{ config('app.name', 'Fuganda') }}">
        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ config('app.name', 'Fuganda') }} — Uganda Property Listings">
        <meta property="og:description" content="Discover properties for rent and sale across Uganda with quick map search.">
        <meta property="og:url" content="{{ url('/') }}">

        <!-- Twitter Card defaults -->
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="{{ config('app.name', 'Fuganda') }} — Uganda Property Listings">
        <meta name="twitter:description" content="Discover properties for rent and sale across Uganda with quick map search.">

        <link rel="canonical" href="{{ url('/') }}">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div id="app"></div>
    </body>
</html>
