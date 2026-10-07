<!-- propiedades de los botones secundarios -->
@props([
    'href' => '#',
    'icon' => true, // por si algún botón no requiere icono
])

<!-- renderizado de los botones secundarios -->
<a href="{{ $href }}"
    {{ $attributes->merge([
        'class' =>
            'inline-flex items-center  mt-8 px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-500 hover:opacity-90 text-white font-bold rounded-xl shadow-[0_0_15px_rgba(59,130,246,0.3)] hover:shadow-[0_0_25px_rgba(59,130,246,0.5)] transition-all gap-2 group',
    ]) }}>

    @if ($icon)
        <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor"
            viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
    @endif

    {{ $slot }}
</a>
