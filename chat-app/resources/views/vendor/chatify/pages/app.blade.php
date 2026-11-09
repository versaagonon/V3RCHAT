@extends(config('chatify.web.layout', 'Chatify::layouts.app'))

@push('styles')
    @if (! empty($isViteDev))
        {{-- Vite handles styles in dev mode via ES module imports --}}
    @else
        <link rel="stylesheet" href="{{ $assetCss }}">
    @endif
@endpush

@section('content')
    <div id="chatify-app" data-config='@json($chatifyBoot)'></div>
    @if (! empty($isViteDev))
        <script type="module" src="{{ $viteDevServer }}/@@vite/client"></script>
        <script type="module" src="{{ $viteDevServer }}/src/main.ts"></script>
    @else
        <script src="{{ $assetJs }}" defer></script>
    @endif
@endsection
