@props([
    'name',
    'class' => 'h-5 w-5',
])

@php
    $iconAttributes = $attributes->class([$class, 'text-current']);
@endphp

@switch($name)
    @case('panel')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75h6.75v6.75H3.75V3.75Zm9.75 0h6.75v6.75H13.5V3.75Zm-9.75 9.75h6.75v6.75H3.75V13.5Zm9.75 0h6.75v6.75H13.5V13.5Z" />
        </svg>
        @break

    @case('instituciones')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M3.75 8.25 12 3l8.25 5.25M6 8.25v10.5m4-10.5v10.5m4-10.5v10.5m4-10.5v10.5" />
        </svg>
        @break

    @case('institucion-item')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M5.25 21V6.75A2.25 2.25 0 0 1 7.5 4.5h9a2.25 2.25 0 0 1 2.25 2.25V21M9.75 9h.008v.008H9.75V9Zm0 3h.008v.008H9.75V12Zm0 3h.008v.008H9.75V15Zm4.5-6h.008v.008h-.008V9Zm0 3h.008v.008h-.008V12Zm0 3h.008v.008h-.008V15Z" />
        </svg>
        @break

    @case('servicios')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m11.42 15.17 6.375 6.375a1.875 1.875 0 0 0 2.652-2.652l-6.375-6.375m-6.75 6.75 6.375-6.375m0 0L8.625 3.67a1.875 1.875 0 0 0-2.652 2.652l6.375 6.375m0 0 3.182-3.182m-6.364 6.364 3.182-3.182" />
        </svg>
        @break

    @case('oficinas')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
        </svg>
        @break

    @case('equipos-group')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5v9m0-9a2.25 2.25 0 0 0-1.5-2.121l-6-2.182a2.25 2.25 0 0 0-1.5 0l-6 2.182A2.25 2.25 0 0 0 4.5 7.5m16.5 0-6.75 2.456a2.25 2.25 0 0 1-1.5 0L6 7.5m15 3.75-6.75 2.456a2.25 2.25 0 0 1-1.5 0L6 11.25m0 0v7.5A2.25 2.25 0 0 0 7.5 21l4.5 1.636a2.25 2.25 0 0 0 1.5 0L18 21a2.25 2.25 0 0 0 1.5-2.121v-7.5" />
        </svg>
        @break

    @case('tipos-equipo')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5 12 3 3 7.5m18 0v9l-9 4.5m9-13.5-9 4.5m-9-4.5 9 4.5m0 0v9m0-9 9-4.5" />
        </svg>
        @break

    @case('equipos')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17.25h4.5m-4.5 0a3 3 0 0 1-3-3V6.75a3 3 0 0 1 3-3h4.5a3 3 0 0 1 3 3v7.5a3 3 0 0 1-3 3m-4.5 0v2.25m4.5-2.25v2.25m-7.5 0h10.5" />
        </svg>
        @break

    @case('actas')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-8.25A2.25 2.25 0 0 0 17.25 3.75H6.75A2.25 2.25 0 0 0 4.5 6v12A2.25 2.25 0 0 0 6.75 20.25h7.5M9 7.5h6m-6 3h6m-6 3h3m3 2.25 2.25 2.25m0 0L21 15.75m-3.75 2.25L15 20.25" />
        </svg>
        @break

    @case('administracion')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M12 3l7.5 3v5.25c0 4.014-2.747 7.5-7.5 9.75-4.753-2.25-7.5-5.736-7.5-9.75V6L12 3Z" />
        </svg>
        @break

    @case('usuarios')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.742-.479 3 3 0 0 0-4.682-2.72m.94 3.198v.001c0 .295-.117.578-.326.787A15.94 15.94 0 0 1 12 21c-2.331 0-4.513-.5-6.427-1.493a1.11 1.11 0 0 1-.326-.787v-.001m12.75 0a3 3 0 0 0-6 0m6 0v.001c0 .295-.117.578-.326.787A15.94 15.94 0 0 1 12 21a15.94 15.94 0 0 1-5.674-1.493A1.11 1.11 0 0 1 6 18.72v-.001m12 0a5.25 5.25 0 0 0-10.5 0m10.5 0v.001c0 .295-.117.578-.326.787A15.94 15.94 0 0 1 12 21a15.94 15.94 0 0 1-5.674-1.493A1.11 1.11 0 0 1 6 18.72v-.001M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
        </svg>
        @break

    @case('auditoria')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 11.25m-6 6h8.25A2.25 2.25 0 0 0 19.5 15V5.25A2.25 2.25 0 0 0 17.25 3H15A2.25 2.25 0 0 0 12.75 1.5h-1.5A2.25 2.25 0 0 0 9 3H6.75A2.25 2.25 0 0 0 4.5 5.25V15a2.25 2.25 0 0 0 2.25 2.25H9Z" />
        </svg>
        @break

    @case('configuracion')
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75m-9.75 6h9.75m-9.75 6h9.75M3.75 6h3m-3 6h3m-3 6h3m3-12a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 6a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm0 6a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" />
        </svg>
        @break

    @default
        <svg {{ $iconAttributes }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
        </svg>
@endswitch
