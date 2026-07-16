<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <!-- Favicon — SVG for modern browsers, ICO fallback for legacy -->
        <link rel="icon" href="/icon.svg" type="image/svg+xml">
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="apple-touch-icon" href="/apple-touch-icon-180x180.png">

        <!-- PWA -->
        <link rel="manifest" href="/build/manifest.webmanifest">
        <meta name="theme-color" content="#0ea5e9">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="Fuganda">
        <title>{{ config('app.name', 'Fuganda') }} — Uganda Property Listings</title>
        <meta name="description" content="Discover properties for rent and sale across Uganda with quick map search.">
        <meta name="robots" content="{{ app()->isProduction() ? 'index,follow' : 'noindex,nofollow' }}">

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

        <!-- Structured data: Organization + WebSite (overridden per-page by usePageMeta) -->
        @php
        $structuredData = json_encode([
            [
                '@context' => 'https://schema.org',
                '@type'    => 'Organization',
                'name'     => config('app.name', 'Fuganda'),
                'url'      => url('/'),
                'description' => 'Uganda property listings — find apartments, houses, land and commercial properties for rent and sale across Uganda.',
            ],
            [
                '@context' => 'https://schema.org',
                '@type'    => 'WebSite',
                'name'     => config('app.name', 'Fuganda'),
                'url'      => url('/'),
                'potentialAction' => [
                    '@type'  => 'SearchAction',
                    'target' => [
                        '@type'       => 'EntryPoint',
                        'urlTemplate' => url('/properties') . '?q={search_term_string}',
                    ],
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        @endphp
        <script type="application/ld+json" data-id="page-jsonld">{!! $structuredData !!}</script>

        <script>window.APP_ENV = '{{ app()->environment() }}';</script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @if(env('GA_MEASUREMENT_ID'))
        <!-- Google Analytics -->
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ env('GA_MEASUREMENT_ID') }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{{ env('GA_MEASUREMENT_ID') }}', { send_page_view: false });
        </script>
        @endif
    </head>
    <body>
        <div id="app"></div>
    </body>
</html>
