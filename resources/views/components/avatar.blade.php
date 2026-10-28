@props(['user', 'size' => 'md'])
{{-- Profilbild, falls vorhanden und für den Betrachter sichtbar; sonst Initialen (Farbe aus der Nutzer-ID) --}}
@php
    $hue = $user ? ($user->id * 47) % 360 : 0;
    $url = $user?->avatarUrl();
@endphp
@if ($url)
    <img {{ $attributes->merge(['class' => 'avatar avatar--'.$size.' avatar--image']) }} src="{{ $url }}" alt="" loading="lazy" decoding="async">
@else
    <span {{ $attributes->merge(['class' => 'avatar avatar--'.$size]) }} style="--avatar-hue: {{ $hue }}" aria-hidden="true">{{ $user?->initials() ?? '?' }}</span>
@endif
