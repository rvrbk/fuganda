<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, maximum-scale=5">
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
        <meta name="apple-mobile-web-app-title" content="MyCanopy">
        @php
        $listing = $listing ?? null;
        $siteTitle = 'MyCanopy — Uganda Property Listings';
        $siteDescription = 'Discover properties for rent and sale across Uganda with quick map search.';
        $pageTitle = $listing ? $listing->title.' | MyCanopy' : $siteTitle;
        $pageDescription = $listing
            ? \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($listing->description ?? ''))) ?: $siteDescription, 200)
            : $siteDescription;
        $pageUrl = $listing ? url('/properties/'.$listing->id) : url('/');
        $pageImage = url('/og.png');
        $pageImageIsListing = false;
        if ($listing) {
            foreach ($listing->images as $image) {
                $path = (string) $image->path;
                if ($path === '' || preg_match('/\.(mp4|webm|mov|m4v)$/i', $path)) {
                    continue;
                }
                $pageImage = match (true) {
                    str_starts_with($path, 'http') => $path,
                    str_starts_with($path, '/') => url($path),
                    default => url('/storage/'.ltrim(preg_replace('#^storage/#', '', $path), '/')),
                };
                $pageImageIsListing = true;
                break;
            }
        }
        @endphp
        <title>{{ $pageTitle }}</title>
        <meta name="description" content="{{ $pageDescription }}">
        <meta name="robots" content="{{ app()->isProduction() ? 'index,follow' : 'noindex,nofollow' }}">

        <!-- Open Graph defaults (overridden per-page by usePageMeta composable) -->
        <meta property="og:site_name" content="MyCanopy">
        <meta property="og:type" content="{{ $listing ? 'article' : 'website' }}">
        <meta property="og:title" content="{{ $pageTitle }}">
        <meta property="og:description" content="{{ $pageDescription }}">
        <meta property="og:url" content="{{ $pageUrl }}">
        <meta property="og:image" content="{{ $pageImage }}">
        @unless ($pageImageIsListing)
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        @endunless
        <meta property="og:image:alt" content="{{ $listing ? $listing->title : 'MyCanopy — Uganda property listings' }}">

        <!-- Twitter Card defaults -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $pageTitle }}">
        <meta name="twitter:description" content="{{ $pageDescription }}">
        <meta name="twitter:image" content="{{ $pageImage }}">

        <link rel="canonical" href="{{ $pageUrl }}">

        <!-- Structured data: Organization + WebSite (overridden per-page by usePageMeta) -->
        @php
        $structuredData = json_encode([
            [
                '@context' => 'https://schema.org',
                '@type'    => 'Organization',
                'name'     => 'MyCanopy',
                'url'      => url('/'),
                'description' => 'Uganda property listings — find apartments, houses, land and commercial properties for rent and sale across Uganda.',
            ],
            [
                '@context' => 'https://schema.org',
                '@type'    => 'WebSite',
                'name'     => 'MyCanopy',
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
        if ($listing) {
            $structuredData = json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'RealEstateListing',
                'name' => $listing->title,
                'description' => $pageDescription,
                'url' => $pageUrl,
                'image' => [$pageImage],
                'address' => array_filter([
                    '@type' => 'PostalAddress',
                    'streetAddress' => $listing->address,
                    'addressLocality' => $listing->city,
                    'addressRegion' => $listing->district,
                    'addressCountry' => 'UG',
                ]),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_HEX_TAG);
        }
        @endphp
        <script type="application/ld+json" data-id="page-jsonld">{!! $structuredData !!}</script>

        <script>window.APP_ENV = '{{ app()->environment() }}';</script>
        <script>window.BACKEND_URL = '{{ rtrim(config("app.url"), "/") }}';</script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        {{-- Page views are sent from the router.afterEach hook in router/index.js. --}}
        {{-- Slate and white with the MyCanopy blue as a top accent, like the app header. --}}
        @include('partials.cookie-consent', [
            'gaId' => env('GA_MEASUREMENT_ID'),
            'autoPageView' => false,
            'theme' => [
                '--cc-bg' => '#ffffff',
                '--cc-fg' => '#0f172a',
                '--cc-border' => '#e2e8f0',
                '--cc-bar' => '3px solid oklch(50% 0.134 242.749)',
                '--cc-radius' => '.5rem',
                '--cc-btn-radius' => '.25rem',
                '--cc-font' => "'Ubuntu', ui-sans-serif, system-ui, sans-serif",
                '--cc-accent' => '#0f172a',
                '--cc-accent-fg' => '#ffffff',
                '--cc-border-strong' => '#cbd5e1',
                '--cc-btn-weight' => '500',
                '--cc-shadow' => '0 10px 30px rgba(15, 23, 42, .15)',
            ],
        ])
        <script defer src="https://lore.verbeek.ug/heat.js" data-key="QrrxreMfn1vSEYL4b7OJjARpXfIaOG3e" data-lore-heat></script>
    </head>
    <body>
        <div id="app"></div>
    </body>
</html>
