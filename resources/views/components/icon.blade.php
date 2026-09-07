@props([
    'name',
    'class' => '',
])

@php
    $path = public_path("assets/icons/{$name}.svg");
@endphp

@if (file_exists($path))
    {!! str_replace(
        '<svg',
        '<svg class="'.$class.'"',
        file_get_contents($path)
    ) !!}
@endif