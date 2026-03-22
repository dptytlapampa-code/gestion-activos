@props([
    'name',
    'class' => 'h-5 w-5',
    'strokeWidth' => 1.8,
])

@php
    $iconAttributes = $attributes->class([$class, 'shrink-0 text-current']);
@endphp

@switch($name)
    @case('dashboard')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="3" y="3" width="7" height="8" rx="1.5" />
            <rect x="14" y="3" width="7" height="5" rx="1.5" />
            <rect x="14" y="12" width="7" height="9" rx="1.5" />
            <rect x="3" y="15" width="7" height="6" rx="1.5" />
        </svg>
        @break

    @case('building-2')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M3 22h18" />
            <path d="M5 22V7.5L12 3l7 4.5V22" />
            <path d="M9 10h.01" />
            <path d="M9 13h.01" />
            <path d="M9 16h.01" />
            <path d="M12 10h.01" />
            <path d="M12 13h.01" />
            <path d="M12 16h.01" />
            <path d="M15 10h.01" />
            <path d="M15 13h.01" />
            <path d="M15 16h.01" />
        </svg>
        @break

    @case('building')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="4" y="3" width="16" height="18" rx="2" />
            <path d="M8 7h.01" />
            <path d="M12 7h.01" />
            <path d="M16 7h.01" />
            <path d="M8 11h.01" />
            <path d="M12 11h.01" />
            <path d="M16 11h.01" />
            <path d="M10 21v-4a2 2 0 0 1 4 0v4" />
        </svg>
        @break

    @case('sitemap')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="9" y="3" width="6" height="4" rx="1" />
            <rect x="3" y="17" width="6" height="4" rx="1" />
            <rect x="15" y="17" width="6" height="4" rx="1" />
            <path d="M12 7v4" />
            <path d="M6 17v-3h12v3" />
        </svg>
        @break

    @case('stethoscope')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M4 3v5a4 4 0 1 0 8 0V3" />
            <path d="M8 3v5" />
            <path d="M12 16h3a3 3 0 1 0-3-3" />
            <circle cx="18" cy="13" r="3" />
            <path d="M15 16v2a4 4 0 0 1-8 0v-2" />
        </svg>
        @break

    @case('map-pin')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M12 22s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11Z" />
            <circle cx="12" cy="11" r="2.5" />
        </svg>
        @break

    @case('door-closed')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M4 21h16" />
            <path d="M7 21V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v16" />
            <path d="M9 21V7h6v14" />
            <path d="M13 14h.01" />
        </svg>
        @break

    @case('boxes')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M3 7.5 12 3l9 4.5-9 4.5-9-4.5Z" />
            <path d="M3 12l9 4.5 9-4.5" />
            <path d="M3 16.5 12 21l9-4.5" />
            <path d="M12 12v9" />
        </svg>
        @break

    @case('screens')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="3" y="4" width="12" height="8" rx="2" />
            <path d="M7 16h4" />
            <path d="M9 12v4" />
            <rect x="13" y="10" width="8" height="6" rx="1.5" />
        </svg>
        @break

    @case('layers')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m12 3 9 4.5-9 4.5-9-4.5L12 3Z" />
            <path d="m3 12 9 4.5 9-4.5" />
            <path d="m3 16.5 9 4.5 9-4.5" />
        </svg>
        @break

    @case('monitor')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="3" y="4" width="18" height="12" rx="2" />
            <path d="M8 20h8" />
            <path d="M12 16v4" />
        </svg>
        @break

    @case('wrench')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m14.7 6.3 2.9-2.8a4 4 0 0 1-5.2 5.9L6.7 15a1.5 1.5 0 1 0 2.1 2.1l5.6-5.7a4 4 0 0 1 5.9-5.2l-2.8 2.9-.6 2.6-2.6.6-2.6-2.6.6-2.6Z" />
        </svg>
        @break

    @case('file-text')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7l-5-5Z" />
            <path d="M14 2v5h5" />
            <path d="M9 13h6" />
            <path d="M9 17h6" />
            <path d="M9 9h2" />
        </svg>
        @break

    @case('shield-check')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m12 3 8 3v6c0 5.2-3.4 8.3-8 9-4.6-.7-8-3.8-8-9V6l8-3Z" />
            <path d="m9 12 2 2 4-4" />
        </svg>
        @break

    @case('users')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
            <circle cx="9" cy="7" r="3" />
            <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
            <path d="M16 4.13a3 3 0 0 1 0 5.74" />
        </svg>
        @break

    @case('clipboard-list')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="5" y="4" width="14" height="18" rx="2" />
            <path d="M9 4.5h6" />
            <path d="M9 10h6" />
            <path d="M9 14h6" />
            <path d="M9 18h4" />
        </svg>
        @break

    @case('sliders-horizontal')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M4 6h4" />
            <path d="M12 6h8" />
            <circle cx="10" cy="6" r="2" />
            <path d="M4 12h10" />
            <path d="M18 12h2" />
            <circle cx="16" cy="12" r="2" />
            <path d="M4 18h2" />
            <path d="M14 18h6" />
            <circle cx="10" cy="18" r="2" />
        </svg>
        @break

    @case('chevron-down')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m6 9 6 6 6-6" />
        </svg>
        @break

    @case('menu')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M4 7h16" />
            <path d="M4 12h16" />
            <path d="M4 17h16" />
        </svg>
        @break

    @case('panel-left-close')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="3" y="4" width="18" height="16" rx="2" />
            <path d="M9 4v16" />
            <path d="m14.5 9-3 3 3 3" />
        </svg>
        @break

    @case('panel-left-open')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="3" y="4" width="18" height="16" rx="2" />
            <path d="M9 4v16" />
            <path d="m11.5 9 3 3-3 3" />
        </svg>
        @break

    @case('plus')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M12 5v14" />
            <path d="M5 12h14" />
        </svg>
        @break

    @case('pencil')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M12 20h9" />
            <path d="m16.5 3.5 4 4L8 20l-5 1 1-5 12.5-12.5Z" />
        </svg>
        @break

    @case('trash-2')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M3 6h18" />
            <path d="M8 6V4h8v2" />
            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
            <path d="M10 11v6" />
            <path d="M14 11v6" />
        </svg>
        @break

    @case('search')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="11" cy="11" r="7" />
            <path d="m20 20-3.5-3.5" />
        </svg>
        @break

    @case('eye')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z" />
            <circle cx="12" cy="12" r="3" />
        </svg>
        @break

    @case('x')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m18 6-12 12" />
            <path d="m6 6 12 12" />
        </svg>
        @break

    @case('alert-circle')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="9" />
            <path d="M12 8v5" />
            <path d="M12 16h.01" />
        </svg>
        @break

    @case('check-circle-2')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="9" />
            <path d="m8.5 12 2.5 2.5L16 9.5" />
        </svg>
        @break

    @case('info')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="9" />
            <path d="M12 10v6" />
            <path d="M12 7h.01" />
        </svg>
        @break

    @case('paperclip')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m21 12.5-8.5 8.5a5 5 0 0 1-7-7L15 4.5a3 3 0 0 1 4.2 4.2l-9.2 9.2a1 1 0 0 1-1.4-1.4l8.1-8.1" />
        </svg>
        @break

    @case('upload')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M12 16V6" />
            <path d="m8 10 4-4 4 4" />
            <path d="M4 18v1a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-1" />
        </svg>
        @break

    @case('download')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M12 6v10" />
            <path d="m8 12 4 4 4-4" />
            <path d="M4 18v1a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-1" />
        </svg>
        @break

    @case('external-link')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M14 5h5v5" />
            <path d="M10 14 19 5" />
            <path d="M19 14v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h4" />
        </svg>
        @break

    @case('image')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="3" y="4" width="18" height="16" rx="2" />
            <path d="m7 15 3-3 3 3 3-3 3 3" />
            <circle cx="9" cy="9" r="1.5" />
        </svg>
        @break

    @case('loader-circle')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-9-9" />
        </svg>
        @break

    @default
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="9" />
        </svg>
@endswitch
