import './bootstrap';
import TomSelect from 'tom-select';

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

        new TomSelect(el, {
            create: false,
            placeholder: placeholder,
            sortField: {
                field: "text",
                direction: "asc"
            }
        });
    });
});
