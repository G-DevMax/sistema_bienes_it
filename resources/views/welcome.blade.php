<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Sistema Informático del Departamento de IT</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <script src="https://cdn.tailwindcss.com"></script>
        @endif
        
        <style>
            body {
                font-family: 'Inter', sans-serif;
            }
            .glass-panel {
                background: rgba(255, 255, 255, 0.05);
                backdrop-filter: blur(16px);
                -webkit-backdrop-filter: blur(16px);
                border: 1px solid rgba(255, 255, 255, 0.1);
            }
            .animate-float {
                animation: float 6s ease-in-out infinite;
            }
            @keyframes float {
                0% { transform: translateY(0px); }
                50% { transform: translateY(-20px); }
                100% { transform: translateY(0px); }
            }
        </style>
    </head>
    <body class="bg-slate-950 text-white min-h-screen flex items-center justify-center relative overflow-hidden">
        
        <!-- Background Elements -->
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] rounded-full bg-blue-600/20 blur-[120px] animate-float"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] rounded-full bg-indigo-600/20 blur-[120px] animate-float" style="animation-delay: -3s;"></div>

        <div class="relative z-10 glass-panel rounded-3xl p-10 md:p-16 max-w-4xl w-full mx-4 shadow-2xl flex flex-col items-center text-center">
            
            <div class="mb-8 inline-flex items-center justify-center p-4 bg-white/5 rounded-2xl border border-white/10 shadow-inner">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                </svg>
            </div>

            <h1 class="text-4xl md:text-5xl font-bold mb-4 bg-gradient-to-r from-blue-400 to-indigo-300 bg-clip-text text-transparent tracking-tight">
                Sistema Informático del Departamento de IT
            </h1>
            
            <p class="text-slate-400 text-lg md:text-xl mb-12 max-w-2xl">
                Plataforma centralizada para la gestión de recursos y asistencia técnica. Seleccione el módulo al que desea acceder.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 w-full max-w-2xl">
                <!-- Card Sistema de Tickets -->
                <a href="#" class="group relative overflow-hidden rounded-2xl bg-white/5 border border-white/10 p-6 transition-all duration-300 hover:bg-white/10 hover:border-white/20 hover:shadow-[0_0_30px_rgba(59,130,246,0.3)] hover:-translate-y-1">
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-600/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="relative z-10 flex flex-col items-center text-center">
                        <div class="p-3 bg-blue-500/20 rounded-xl mb-4 text-blue-300 group-hover:scale-110 group-hover:text-blue-200 transition-all duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                            </svg>
                        </div>
                        <h2 class="text-xl font-semibold text-white mb-2">Sistema de Tickets</h2>
                        <p class="text-sm text-slate-400 group-hover:text-slate-300 transition-colors">Gestión de incidentes, solicitudes de soporte y mesa de ayuda.</p>
                        <div class="mt-4 flex items-center text-sm font-medium text-blue-400 group-hover:text-blue-300">
                            Próximamente
                        </div>
                    </div>
                </a>

                <!-- Card Sistema de Bienes -->
                <a href="{{ route('login') }}" class="group relative overflow-hidden rounded-2xl bg-white/5 border border-white/10 p-6 transition-all duration-300 hover:bg-white/10 hover:border-white/20 hover:shadow-[0_0_30px_rgba(99,102,241,0.3)] hover:-translate-y-1">
                    <div class="absolute inset-0 bg-gradient-to-br from-indigo-600/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="relative z-10 flex flex-col items-center text-center">
                        <div class="p-3 bg-indigo-500/20 rounded-xl mb-4 text-indigo-300 group-hover:scale-110 group-hover:text-indigo-200 transition-all duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                        <h2 class="text-xl font-semibold text-white mb-2">Sistema de Bienes</h2>
                        <p class="text-sm text-slate-400 group-hover:text-slate-300 transition-colors">Control de inventario, asignación de equipos y movimientos.</p>
                        <div class="mt-4 flex items-center text-sm font-medium text-indigo-400 group-hover:text-indigo-300">
                            Acceder al módulo
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="mt-12 text-sm text-slate-500">
                &copy; {{ date('Y') }} Departamento de IT. Todos los derechos reservados.
            </div>
        </div>
    </body>
</html>
