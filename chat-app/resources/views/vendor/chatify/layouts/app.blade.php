<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), config('chatify.rtl_locales', ['ar']), true) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('chatify::chatify.ui.app_name', ['name' => config('chatify.name', 'V3RCHAT')]) }}</title>
    <link rel="icon" type="image/png" href="{{ asset('vendor/chatify/logo_transparant.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('vendor/chatify/logo_transparant.png') }}">
    @stack('styles')
</head>
<body class="chatify:antialiased chatify:m-0 chatify:overflow-hidden chatify-dark" style="overscroll-behavior: none; touch-action: manipulation; background-color: #000000; color: #ffffff;">
    @yield('content')
</body>
</html>
