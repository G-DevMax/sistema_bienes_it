@props([
    // propiedades dinamicas de acuerdo al index en que se integra la barra de busqueda
    'placeholder' => 'Buscar registros...',
    'action' => url()->current(), // se toma la ruta actual donde se encuentra la barra de busqueda
    'name' => 'search', // nombre del campo de busqueda que se enviara por GET
])

<form action="{{ $action }}" method="GET" class="w-full max-w-md mb-4">
    <div class="relative flex items-center">
        <!-- Ícono de lupa -->
        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
            <svg class="w-4 h-4 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 20 20">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
            </svg>
        </div>

        <!-- Input de búsqueda -->
        <input type="text" name="{{ $name }}" value="{{ request($name) }}" placeholder="{{ $placeholder }}"
            class="w-full pl-10 pr-24 py-2 text-sm text-white border border-blue-500 rounded-lg bg-gray- focus:ring-blue-500 focus:border-blue-500 outline-none">

        <!-- Botón para limpiar (solo aparece si hay una búsqueda activa) -->
        @if (request()->filled($name))
            <a href="{{ $action }}" class="absolute right-20 text-gray-400 hover:text-gray-600 px-2"
                title="Limpiar búsqueda">
                &#10005;
            </a>
        @endif

        <!-- Botón de envío -->
        <button type="submit"
            class="text-white absolute right-1.5 bottom-1.5 bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-md text-xs px-3 py-1.5">
            Buscar
        </button>
    </div>
</form>
