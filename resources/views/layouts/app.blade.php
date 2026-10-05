<!DOCTYPE html>
<html lang="es" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sistema de Bienes IT')</title>
    <meta name="description" content="Dashboard del Sistema de Bienes IT">

    <!-- Vite / Tailwind CSS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body
    class="bg-[#293462] text-white min-h-screen flex flex-col font-sans antialiased selection:bg-[#1CD6CE]/30 selection:text-white">
    <!-- Fondo decorativo con degradados -->
    <div class="fixed inset-0 z-[-1] overflow-hidden pointer-events-none">
        <div class="absolute -top-[40%] -left-[10%] w-[70%] h-[70%] rounded-full bg-[#1CD6CE]/15 blur-[120px]"></div>
        <div class="absolute top-[20%] -right-[10%] w-[60%] h-[60%] rounded-full bg-[#D61C4E]/15 blur-[120px]"></div>
    </div>

    <!-- Navbar -->
    @if (auth()->check())
        <nav
            class="sticky top-0 z-50 backdrop-blur-xl bg-[#293462]/80 border-b border-white/10 px-6 py-4 flex justify-between items-center shadow-lg">
            <a href="/"
                class="text-2xl font-bold bg-gradient-to-r from-[#1CD6CE] to-[#FEDB39] bg-clip-text text-transparent hover:opacity-80 transition-opacity">
                Sistema Bienes IT (FC11)
            </a>
            <div class="flex items-center space-x-2 sm:space-x-4">
                <a href="{{ route('items.index') }}"
                    class="px-4 py-2 rounded-lg text-sm font-medium text-white/80 hover:text-white hover:bg-white/10 transition-all">
                    Items
                </a>
                <a href="{{ route('movimientos.index') }}"
                    class="px-4 py-2 rounded-lg text-sm font-medium text-white/80 hover:text-white hover:bg-white/10 transition-all">
                    Movimientos
                </a>
                <a href="{{ route('usuarios.index') }}"
                    class="px-4 py-2 rounded-lg text-sm font-medium text-white/80 hover:text-white hover:bg-white/10 transition-all">
                    Usuarios
                </a>
                <a href="{{ route('funcionarios.index') }}"
                    class="px-4 py-2 rounded-lg text-sm font-medium text-white/80 hover:text-white hover:bg-white/10 transition-all">
                    Funcionarios
                </a>
                <a href="{{ route('dependencias.index') }}"
                    class="px-4 py-2 rounded-lg text-sm font-medium text-white/80 hover:text-white hover:bg-white/10 transition-all">
                    Dependencias
                </a>

                <form action="{{ route('logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit"
                        class="px-4 py-2 rounded-lg text-sm font-medium text-red-500 hover:text-red-400 hover:bg-white/10 transition-all">
                        Cerrar Sesión
                    </button>
                </form>
            </div>

        </nav>
    @endif
    <!-- Contenido Principal -->
    <main class="flex-1 flex flex-col items-center justify-center p-6 md:p-10 w-full max-w-7xl mx-auto">
        @yield('content')
    </main>
</body>

</html>
