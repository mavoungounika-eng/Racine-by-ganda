@props(['capability', 'plan' => 'Officiel'])

@if(auth()->user()->hasCapability($capability))
    {{ $slot }}
@else
    <x-creator.upgrade-message :capability="$capability" :plan="$plan">
        @isset($message)
            @slot('message', $message)
        @endisset
    </x-creator.upgrade-message>
@endif

