<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <title data-inertia>{{ $seo['title'] ?? app(\PnShop\Settings\Settings::class)->get('store.name') }}</title>
        @isset($seo)
            @if ($seo['description'])
                <meta name="description" content="{{ $seo['description'] }}">
            @endif
            <meta name="robots" content="{{ $seo['robots'] }}">
            <link rel="canonical" href="{{ $seo['canonical'] }}">
            @foreach ($seo['alternates'] as $hreflang => $href)
                <link rel="alternate" hreflang="{{ $hreflang }}" href="{{ $href }}">
            @endforeach
            @if ($seo['alternates'] !== [])
                <link rel="alternate" hreflang="x-default" href="{{ reset($seo['alternates']) }}">
            @endif
            <meta property="og:site_name" content="{{ $seo['site_name'] }}">
            <meta property="og:title" content="{{ $seo['title'] }}">
            <meta property="og:type" content="{{ $seo['type'] }}">
            <meta property="og:url" content="{{ $seo['canonical'] }}">
            <meta property="og:locale" content="{{ $seo['locale'] }}">
            @if ($seo['description'])
                <meta property="og:description" content="{{ $seo['description'] }}">
            @endif
            @if ($seo['image'])
                <meta property="og:image" content="{{ $seo['image'] }}">
                <meta name="twitter:card" content="summary_large_image">
            @endif
            @if ($seo['json_ld_script'] !== null)
                <script type="application/ld+json">{!! $seo['json_ld_script'] !!}</script>
            @endif
        @endisset

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @routes
        @if (! empty($themeBuild))
            {{-- The active theme's prebuilt bundle. --}}
            @vite($themeEntries, $themeBuild)
        @else
            @viteReactRefresh
            @vite(['resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        @endif
        @if (! empty($themeCss))
            <style>{!! $themeCss !!}</style>
        @endif
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
