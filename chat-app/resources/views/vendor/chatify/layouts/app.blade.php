<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), config('chatify.rtl_locales', ['ar']), true) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('chatify::chatify.ui.app_name', ['name' => config('chatify.name', 'V3RCHAT')]) }}</title>

    <!-- PWA & Mobile Web App Meta Tags -->
    <meta name="theme-color" content="#000000" media="(prefers-color-scheme: dark)">
    <meta name="theme-color" content="#000000">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ config('chatify.name', 'V3RCHAT') }}">
    <meta name="application-name" content="{{ config('chatify.name', 'V3RCHAT') }}">
    <meta name="format-detection" content="telephone=no">

    <!-- Web App Manifest -->
    <link rel="manifest" href="/manifest.json">

    <!-- Apple Touch Icons (iOS Home Screen) -->
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon-180x180.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/icons/apple-touch-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="167x167" href="/icons/apple-touch-icon-167x167.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/icons/apple-touch-icon-180x180.png">

    <!-- Standard Favicons -->
    <link rel="icon" type="image/png" sizes="32x32" href="/icons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/icons/favicon-16x16.png">
    <link rel="icon" type="image/png" href="{{ asset('vendor/chatify/logo_transparant.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('vendor/chatify/logo_transparant.png') }}">

    <!-- Typography Preloads -->
    <link rel="preload" href="{{ asset('vendor/chatify/fonts/SF-Pro-Text-Regular.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('vendor/chatify/fonts/SF-Pro-Text-Medium.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('vendor/chatify/fonts/SF-Pro-Text-Semibold.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('vendor/chatify/fonts/SF-Pro-Display-Bold.woff2') }}" as="font" type="font/woff2" crossorigin>

    @stack('styles')
</head>
<body class="chatify:antialiased chatify:m-0 chatify:overflow-hidden chatify-dark" style="overscroll-behavior: none; touch-action: manipulation; background-color: #000000; color: #ffffff; font-family: 'SF Pro Text', 'SF Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-tap-highlight-color: transparent;">
    @yield('content')
</body>
</html>
