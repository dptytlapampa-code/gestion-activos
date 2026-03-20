@props([
    'name',
    'class' => 'h-5 w-5',
])

@php
    $aliases = [
        'panel' => 'dashboard',
        'instituciones' => 'building-2',
        'institucion-item' => 'building',
        'servicios' => 'sitemap',
        'oficinas' => 'door-closed',
        'equipos-group' => 'screens',
        'tipos-equipo' => 'layers',
        'equipos' => 'monitor',
        'mantenimiento' => 'wrench',
        'actas' => 'file-text',
        'administracion' => 'shield-check',
        'usuarios' => 'users',
        'auditoria' => 'clipboard-list',
        'configuracion' => 'sliders-horizontal',
    ];
@endphp

<x-icon :name="$aliases[$name] ?? 'plus'" :class="$class" {{ $attributes }} />
