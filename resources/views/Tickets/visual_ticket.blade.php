<!DOCTYPE html>
<html lang="es" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Creado #{{ $numeroTicket }} | InfraDesk</title>
    <meta name="description" content="Confirmación de ticket de soporte técnico">
    <link rel="icon" type="image/png" href="{{ asset('img/logo_app.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body
    class="bg-neutral-950 text-zinc-100 min-h-screen flex flex-col justify-center px-4 py-6 sm:py-10 font-sans antialiased">
    <main class="w-full max-w-lg mx-auto">
        <header class="mb-6 text-center sm:text-left">
            <h1 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">
                Ticket Registrado
            </h1>
        </header>

        <section class="bg-zinc-900 border border-zinc-800 rounded-2xl p-5 sm:p-7 shadow-xl text-center space-y-6">
            <div class="space-y-2">
                <p class="text-base sm:text-lg text-zinc-200 leading-relaxed">
                    Su ticket con número <strong class="text-white">#{{ $numeroTicket }}</strong> fue creado exitosamente, en breve un técnico asistirá su problema.
                </p>
            </div>

            <div class="space-y-3 pt-2">
                <button type="button" id="btn-copiar-ticket" data-ticket-numero="{{ $numeroTicket }}"
                    class="w-full py-3.5 px-6 rounded-xl bg-neutral-950 hover:bg-zinc-800 border border-zinc-700 hover:border-indigo-500 text-zinc-200 font-semibold text-base transition-colors flex items-center justify-center gap-2 cursor-pointer">
                    <svg class="w-5 h-5 text-indigo-400 shrink-0" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z">
                        </path>
                    </svg>
                    <span id="texto-copiar-ticket">Copiar número de ticket</span>
                </button>

                <a href="{{ route('tickets.create') }}"
                    class="w-full py-3.5 px-6 rounded-xl bg-indigo-600 hover:bg-indigo-500 active:bg-violet-700 text-white font-bold text-base transition-colors flex items-center justify-center">
                    Crear otro ticket
                </a>
            </div>
        </section>

        <footer class="mt-6 text-center text-xs text-zinc-500">
            <p>© {{ date('Y') }} - InfraDesk (SGDI) | Departamento de Informática y TICs</p>
        </footer>
    </main>

    <script>
        document.getElementById('btn-copiar-ticket')?.addEventListener('click', async function() {
            const numero = this.dataset.ticketNumero;
            const texto = document.getElementById('texto-copiar-ticket');

            if (navigator.clipboard?.writeText) {
                await navigator.clipboard.writeText(numero).catch(() => {});
            } else {
                const aux = document.createElement('textarea');
                aux.value = numero;
                aux.style.position = 'fixed';
                aux.style.opacity = '0';
                document.body.appendChild(aux);
                aux.select();
                document.execCommand('copy');
                aux.remove();
            }

            if (texto) {
                texto.textContent = 'Número de ticket copiado';
            }
            this.classList.remove('border-zinc-700', 'text-zinc-200');
            this.classList.add('border-emerald-500', 'text-emerald-400');
        });
    </script>
</body>

</html>
