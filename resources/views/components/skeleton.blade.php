@props([
    'type'   => 'text',
    'width'  => null,
    'height' => null,
])

@php
    $baseClass = 'racine-skeleton';
    $typeClass = match($type) {
        'title'  => 'racine-skeleton--title',
        'avatar' => 'racine-skeleton--avatar',
        'btn'    => 'racine-skeleton--btn',
        'image'  => 'racine-skeleton--image',
        default  => 'racine-skeleton--text',
    };
    $inlineStyle = '';
    if ($width)  $inlineStyle .= 'width:'  . $width  . ';';
    if ($height) $inlineStyle .= 'height:' . $height . ';';
@endphp

<span
    {{ $attributes->merge(['class' => $baseClass . ' ' . $typeClass]) }}
    @if($inlineStyle) style="{{ $inlineStyle }}" @endif
    aria-hidden="true"
    role="presentation"
></span>
