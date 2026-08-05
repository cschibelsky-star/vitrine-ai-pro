@if(auth()->check() && auth()->user()?->isAdmin())
    @php
        $viaFactoryConfig = [
            'contextUrl' => route('via.factory.context'),
            'chatUrl' => route('via.factory.chat'),
            'actionUrl' => route('via.factory.action'),
            'csrfToken' => csrf_token(),
            'user' => [
                'id' => auth()->id(),
                'name' => auth()->user()?->name,
            ],
            'version' => '1.0.0',
        ];
    @endphp
    <div id="via-factory-root" aria-live="polite"></div>
    <script>
        window.VIA_FACTORY_CONFIG = {{ Illuminate\Support\Js::from($viaFactoryConfig) }};
    </script>
    <script src="{{ asset('js/via-factory.js') }}?v=1.0.0" defer></script>
@endif
