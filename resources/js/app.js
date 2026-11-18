import './bootstrap';
import TomSelect from 'tom-select';

window.TomSelect = TomSelect;

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.searchable-select').forEach((el) => {
        // Limpiar espacios en blanco o saltos de línea accidentales en los <option> generados por Blade
        el.querySelectorAll('option').forEach((opt) => {
            opt.textContent = opt.textContent.replace(/\s+/g, ' ').trim();
        });

        // Permitir personalizar el placeholder mediante atributo 'placeholder', 'data-placeholder' o desde la opción vacía
        const placeholder = el.getAttribute('placeholder') ||
            el.getAttribute('data-placeholder') ||
            el.querySelector('option[value=""]')?.textContent ||
            undefined;

        const sortField = el.dataset.sort === 'none'
            ? [{ field: '$order' }, { field: '$score' }]
            : {
                field: "text",
                direction: "asc"
            };

        new TomSelect(el, {
            create: false,
            placeholder: placeholder,
            sortField: sortField
        });
    });
});

// Funcion para bloquear temporalmente algun boton de envio de un formulario mientras la pagina esta cargando
document.addEventListener('DOMContentLoaded', () => {
    const forms = document.querySelectorAll('form');

    forms.forEach(form => {
        form.addEventListener('submit', function (e) {
            // Si el formulario ya se está enviando, cancelar el nuevo intento
            if (this.dataset.submitting === 'true') {
                e.preventDefault();
                return;
            }

            // Marcar el formulario como "enviando"
            this.dataset.submitting = 'true';

            // Buscar el botón de envío y deshabilitarlo
            const submitBtn = this.querySelector('button[type="submit"], input[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');

                // Cambiar el texto temporalmente
                if (submitBtn.tagName === 'BUTTON') {
                    submitBtn.innerText = 'Procesando...';
                } else {
                    submitBtn.value = 'Procesando...';
                }
            }
        });
    });
});

// Lógica compartida para formularios de creación de tickets (contador de palabras y campos de reunión)
document.addEventListener('DOMContentLoaded', () => {
    const inputTitulo = document.getElementById('descripcion_problema');
    const contador = document.getElementById('contador-palabras');
    const camposReunion = document.getElementById('campos-reunion');
    const radiosTipo = document.querySelectorAll('input[name="tipo_problema"]');

    if (!inputTitulo || !contador || !camposReunion || !radiosTipo.length) {
        return;
    }

    const TITULO_REUNION = 'Asistencia a Reunión';
    const inputFecha = document.getElementById('fecha_reunion');
    const inputHorario = document.getElementById('horario_reunion');
    const inputDetalles = document.getElementById('detalles_reunion');
    const claseMuted = contador.classList.contains('text-zinc-400') ? 'text-zinc-400' : 'text-white/50';

    function actualizarContador() {
        const palabras = inputTitulo.value.trim().split(/\s+/).filter(Boolean).length;
        const excede = palabras > 100;
        contador.textContent = `${palabras} / 100 palabras`;
        contador.classList.toggle('text-red-400', excede);
        contador.classList.toggle(claseMuted, !excede);
        inputTitulo.setCustomValidity(excede ? 'El título del problema no puede superar las 100 palabras.' : '');
    }

    function actualizarModoReunion() {
        const seleccionado = document.querySelector('input[name="tipo_problema"]:checked')?.value;
        const esReunion = seleccionado === TITULO_REUNION;

        camposReunion.classList.toggle('hidden', !esReunion);
        [inputFecha, inputHorario, inputDetalles].forEach((campo) => {
            if (campo) {
                campo.disabled = !esReunion;
                campo.required = esReunion;
            }
        });

        inputTitulo.readOnly = esReunion;
        inputTitulo.classList.toggle('opacity-70', esReunion);
        inputTitulo.classList.toggle('cursor-not-allowed', esReunion);

        if (esReunion) {
            inputTitulo.value = TITULO_REUNION;
        } else if (inputTitulo.value === TITULO_REUNION) {
            inputTitulo.value = '';
        }

        actualizarContador();
    }

    inputTitulo.addEventListener('input', actualizarContador);
    radiosTipo.forEach((radio) => radio.addEventListener('change', actualizarModoReunion));
    actualizarModoReunion();
});
