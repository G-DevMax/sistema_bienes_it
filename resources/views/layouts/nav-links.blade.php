@if ($esModuloInformes)
    @can('informes.ver')
        <a href="{{ route('informes.index') }}"
            class="block px-4 py-2 rounded-lg text-sm font-medium text-white/80 hover:text-white hover:bg-white/10 transition-all">
            Informes
        </a>
    @endcan
    @can('detalles.ver')
        <a href="{{ route('detalles-problemas.index') }}"
            class="block px-4 py-2 rounded-lg text-sm font-medium bg-blue-500/15 text-blue-300 border border-blue-400/30 hover:bg-blue-500/25 hover:text-white transition-all">
            Registrar Problema Común
        </a>
        <a href="{{ route('detalles-soluciones.index') }}"
            class="block px-4 py-2 rounded-lg text-sm font-medium bg-emerald-500/15 text-emerald-300 border border-emerald-400/30 hover:bg-emerald-500/25 hover:text-white transition-all">
            Registrar Solución Común
        </a>
    @endcan
@elseif ($esModuloTickets)
    @can('tickets.ver')
        <a href="{{ route('tickets.dashboard') }}"
            class="block px-4 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('tickets.dashboard') ? 'bg-white/15 text-white' : 'text-white/80 hover:text-white hover:bg-white/10' }}">
            Inicio
        </a>
        <a href="{{ route('tickets.index') }}"
            class="block px-4 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('tickets.index', 'tickets.show') ? 'bg-white/15 text-white' : 'text-white/80 hover:text-white hover:bg-white/10' }}">
            Listado de Tickets
        </a>
        <a href="{{ route('tickets.estadisticas') }}"
            class="block px-4 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('tickets.estadisticas') ? 'bg-white/15 text-white' : 'text-white/80 hover:text-white hover:bg-white/10' }}">
            Estadísticas
        </a>
    @endcan
    @can('tickets.crear')
        <a href="{{ route('tickets.create-manual') }}"
            class="block px-4 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('tickets.create-manual') ? 'bg-white/15 text-white' : 'text-white/80 hover:text-white hover:bg-white/10' }}">
            Crear Ticket
        </a>
    @endcan
@else
    @can('bienes.ver')
        <a href="{{ route('menu_bientes') }}"
            class="block px-4 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('menu_bientes') ? 'bg-white/15 text-white' : 'text-white/80 hover:text-white hover:bg-white/10' }}">
            Inicio
        </a>
        <a href="{{ route('items.index') }}"
            class="block px-4 py-2 rounded-lg text-sm font-medium text-white/80 hover:text-white hover:bg-white/10 transition-all">
            Items
        </a>
        <a href="{{ route('movimientos.index') }}"
            class="block px-4 py-2 rounded-lg text-sm font-medium text-white/80 hover:text-white hover:bg-white/10 transition-all">
            Movimientos
        </a>
    @endcan
    @can('funcionarios.ver')
        <a href="{{ route('funcionarios.index') }}"
            class="block px-4 py-2 rounded-lg text-sm font-medium text-white/80 hover:text-white hover:bg-white/10 transition-all">
            Funcionarios
        </a>
    @endcan
    @can('usuarios.gestionar')
        <a href="{{ route('usuarios.index') }}"
            class="block px-4 py-2 rounded-lg text-sm font-medium text-white/80 hover:text-white hover:bg-white/10 transition-all">
            Usuarios
        </a>
    @endcan
@endif

<form action="{{ route('logout') }}" method="POST" class="w-full md:w-auto">
    @csrf
    <button type="submit"
        class="w-full md:w-auto text-left md:text-center px-4 py-2 rounded-lg text-sm font-medium text-red-500 hover:text-red-400 hover:bg-white/10 transition-all cursor-pointer">
        Cerrar Sesión
    </button>
</form>
