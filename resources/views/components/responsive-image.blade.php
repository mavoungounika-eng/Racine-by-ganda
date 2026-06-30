@props([
    'src' => '',
    'alt' => '',
    'eager' => false,
    'class' => '',
    'width' => null,
    'height' => null,
])

@php
    $srcset = responsive_srcset($src);
    $loading = $eager ? 'eager' : 'lazy';
    $sizes = '(max-width: 576px) 400px, (max-width: 992px) 800px, 1200px';
@endphp

<img src="{{ $src }}"
     srcset="{{ $srcset }}"
     sizes="{{ $sizes }}"
     loading="{{ $loading }}"
     alt="{{ $alt }}"
     @if($width) width="{{ $width }}" @endif
     @if($height) height="{{ $height }}" @endif
     class="{{ $class }}">
