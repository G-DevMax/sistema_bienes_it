<!DOCTYPE html>
<html lang="es" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        {{ request()->routeIs('tickets.dashboard') && isset($pendientes) ? "({$pendientes}) " : '' }}@yield('title', 'Sistema de Bienes IT')
    </title>
    <meta name="description" content="Dashboard del Sistema de Bienes IT">
    <link rel="icon" type="image/png" href="{{ asset('../img/logo_app.png') }}">


    <!-- Vite / Tailwind CSS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body
    class="bg-slate-950 text-white min-h-screen flex flex-col font-sans antialiased selection:bg-blue-600/30 selection:text-white">
    <!-- Fondo decorativo con degradados estáticos -->
    <div class="fixed inset-0 z-[-1] pointer-events-none transform-gpu"
        style="background: radial-gradient(circle at 25% 0%, rgba(37, 99, 235, 0.2), transparent 55%), radial-gradient(circle at 80% 50%, rgba(79, 70, 229, 0.2), transparent 50%);">
    </div>

    <!-- Navbar -->
    @if (auth()->check())
        @php
            $esModuloInformes = request()->routeIs('informes.*', 'detalles-problemas.*', 'detalles-soluciones.*');
            $esModuloTickets = request()->routeIs('tickets.*');
            $tituloModulo = match (true) {
                $esModuloInformes => 'InfraDesk - Informes IT',
                $esModuloTickets => 'InfraDesk - Tickets IT',
                default => 'InfraDesk - Bienes IT (FC11)',
            };
        @endphp
        <nav
            class="sticky top-0 z-50 backdrop-blur-xl bg-white/5 border-b border-white/10 px-4 sm:px-6 py-4 flex justify-between items-center gap-3 shadow-lg">
            <a href="/"
                class="min-w-0 truncate text-lg sm:text-2xl font-bold bg-gradient-to-r from-blue-400 to-indigo-300 bg-clip-text text-transparent hover:opacity-80 transition-opacity">
                {{ $tituloModulo }}
            </a>

            <div class="hidden md:flex items-center gap-2 lg:gap-4 shrink-0">
                @include('layouts.nav-links')
            </div>

            <details class="md:hidden relative shrink-0">
                <summary
                    class="list-none [&::-webkit-details-marker]:hidden cursor-pointer px-3 py-2 rounded-lg text-white/80 hover:text-white hover:bg-white/10 transition-all select-none"
                    aria-label="Abrir menú de navegación">
                    <span class="text-xl leading-none" aria-hidden="true">☰</span>
                </summary>
                <div
                    class="absolute right-0 mt-2 w-60 rounded-xl bg-slate-900 border border-white/15 shadow-2xl p-2 flex flex-col gap-1 z-50">
                    @include('layouts.nav-links')
                </div>
            </details>
        </nav>
    @endif
    <!-- Contenido Principal -->
    <main class="flex-1 flex flex-col items-center justify-center p-4 md:p-10 w-full max-w-7xl mx-auto">
        @yield('content')
    </main>
</body>

</html>
