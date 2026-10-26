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
