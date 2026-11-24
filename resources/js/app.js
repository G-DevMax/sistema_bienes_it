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
    const TITULO_CONSULTA = 'Consulta';
    const TITULO_INSUMOS = 'Insumos';
    const bloqueTitulo = document.getElementById('bloque-titulo-problema') || inputTitulo.parentElement;
    const bloqueSelectDetalle = document.getElementById('bloque-select-detalle');
    const selectDetalle = document.getElementById('detalle_id');
    const bloqueDescripcionOtro = document.getElementById('bloque-descripcion-otro');
    const bloqueCantidadInsumo = document.getElementById('bloque-cantidad-insumo');
    const inputCantidadInsumo = document.getElementById('cantidad_insumo');
    const detallesData = selectDetalle ? JSON.parse(selectDetalle.dataset.detalles || '[]') : [];
    let detalleInicial = selectDetalle ? (selectDetalle.dataset.oldDetalle || '') : '';
    const inputFecha = document.getElementById('fecha_reunion');
    const inputHorario = document.getElementById('horario_reunion');
    const inputDetalles = document.getElementById('detalles_reunion');
    const claseMuted = contador.classList.contains('text-zinc-400') ? 'text-zinc-400' : 'text-white/50';

    function actualizarContador() {
        const palabras = inputTitulo.value.trim().split(/\s+/).filter(Boolean).length;
        const excede = !inputTitulo.disabled && palabras > 100;
        contador.textContent = `${palabras} / 100 palabras`;
        contador.classList.toggle('text-red-400', excede);
        contador.classList.toggle(claseMuted, !excede);
        inputTitulo.setCustomValidity(excede ? 'El título del problema no puede superar las 100 palabras.' : '');
    }

    function actualizarCantidadInsumo(tipo) {
        if (!bloqueCantidadInsumo || !inputCantidadInsumo || !selectDetalle) {
            return;
        }

        const mostrar = tipo === TITULO_INSUMOS && !selectDetalle.disabled && selectDetalle.value !== '';
        bloqueCantidadInsumo.classList.toggle('hidden', !mostrar);
        inputCantidadInsumo.disabled = !mostrar;
        inputCantidadInsumo.required = mostrar;

        if (!mostrar) {
            inputCantidadInsumo.value = '1';
            inputCantidadInsumo.setCustomValidity('');
            return;
        }

        if (inputCantidadInsumo.value === '') {
            inputCantidadInsumo.value = '1';
        }
    }

    function actualizarDescripcionOtro() {
        if (!selectDetalle || !bloqueDescripcionOtro) {
            return;
        }

        const seleccionado = document.querySelector('input[name="tipo_problema"]:checked')?.value;
        const esOtro = !selectDetalle.disabled && selectDetalle.value === 'otro';
        bloqueDescripcionOtro.classList.toggle('hidden', !esOtro);
        inputTitulo.disabled = !esOtro;
        inputTitulo.required = esOtro;

        if (!esOtro) {
            inputTitulo.value = '';
        }

        actualizarContador();
        actualizarCantidadInsumo(seleccionado);
    }

    function cargarOpcionesProblema(tipo, valorPorSeleccionar) {
        if (!selectDetalle) {
            return;
        }

        const esConsulta = tipo === TITULO_CONSULTA;
        if (bloqueSelectDetalle) {
            bloqueSelectDetalle.classList.toggle('hidden', esConsulta);
        }

        selectDetalle.innerHTML = '';

        if (!tipo) {
            selectDetalle.disabled = true;
            selectDetalle.required = false;
            const optPlaceholder = document.createElement('option');
            optPlaceholder.value = '';
            optPlaceholder.textContent = 'Primero selecciona un tipo de problema...';
            selectDetalle.appendChild(optPlaceholder);
            actualizarDescripcionOtro();
            return;
        }

        if (esConsulta) {
            selectDetalle.disabled = false;
            selectDetalle.required = true;
            const optOtro = document.createElement('option');
            optOtro.value = 'otro';
            optOtro.textContent = 'Otro (especificar)';
            optOtro.selected = true;
            selectDetalle.appendChild(optOtro);
            selectDetalle.value = 'otro';
            actualizarDescripcionOtro();
            return;
        }

        const filtrados = detallesData.filter((d) => d.tipo_problema === tipo);
        selectDetalle.disabled = false;
        selectDetalle.required = true;

        if (filtrados.length > 0) {
            const optPlaceholder = document.createElement('option');
            optPlaceholder.value = '';
            optPlaceholder.disabled = true;
            optPlaceholder.selected = true;
            optPlaceholder.textContent = 'Selecciona el problema...';
            selectDetalle.appendChild(optPlaceholder);

            filtrados.forEach((d) => {
                const opt = document.createElement('option');
                opt.value = String(d.id);
                opt.textContent = d.detalle;
                selectDetalle.appendChild(opt);
            });
        }

        const optOtro = document.createElement('option');
        optOtro.value = 'otro';
        optOtro.textContent = 'Otro (especificar)';
        selectDetalle.appendChild(optOtro);

        const existe = valorPorSeleccionar && (
            valorPorSeleccionar === 'otro' ||
            filtrados.some((d) => String(d.id) === String(valorPorSeleccionar))
        );

        if (existe) {
            selectDetalle.value = String(valorPorSeleccionar);
        } else if (filtrados.length === 0) {
            selectDetalle.value = 'otro';
        }

        actualizarDescripcionOtro();
    }

    function actualizarModoReunion(usarInicial = false) {
        const seleccionado = document.querySelector('input[name="tipo_problema"]:checked')?.value;
        const esReunion = seleccionado === TITULO_REUNION;

        camposReunion.classList.toggle('hidden', !esReunion);
        [inputFecha, inputHorario, inputDetalles].forEach((campo) => {
            if (campo) {
                campo.disabled = !esReunion;
                campo.required = esReunion;
            }
        });

        if (bloqueTitulo) {
            bloqueTitulo.classList.toggle('hidden', esReunion);
        }

        if (selectDetalle) {
            if (esReunion) {
                selectDetalle.disabled = true;
                selectDetalle.required = false;
                if (bloqueSelectDetalle) {
                    bloqueSelectDetalle.classList.remove('hidden');
                }
                if (bloqueDescripcionOtro) {
                    bloqueDescripcionOtro.classList.add('hidden');
                }
                inputTitulo.disabled = true;
                inputTitulo.required = false;
                inputTitulo.value = '';
                actualizarContador();
                actualizarCantidadInsumo(seleccionado);
            } else {
                const valor = usarInicial ? detalleInicial : '';
                detalleInicial = '';
                cargarOpcionesProblema(seleccionado, valor);
            }
            return;
        }

        inputTitulo.disabled = esReunion;
        inputTitulo.required = !esReunion;

        if (esReunion) {
            inputTitulo.value = '';
        }

        actualizarContador();
    }

    inputTitulo.addEventListener('input', actualizarContador);
    if (inputCantidadInsumo) {
        inputCantidadInsumo.addEventListener('change', () => {
            const num = parseInt(inputCantidadInsumo.value, 10);
            inputCantidadInsumo.value = String(Number.isNaN(num) ? 1 : Math.min(12, Math.max(1, num)));
        });
    }
    if (selectDetalle) {
        selectDetalle.addEventListener('change', actualizarDescripcionOtro);
    }
    radiosTipo.forEach((radio) => radio.addEventListener('change', () => actualizarModoReunion(false)));
    actualizarModoReunion(true);

    // Adjuntar foto desde cámara trasera en dispositivos móviles y comprimir a .webp
    const contenedorFoto = document.getElementById('contenedor-foto-movil');
    const btnAdjuntarFoto = document.getElementById('btn-adjuntar-foto');
    const inputFoto = document.getElementById('imagen_problema');
    const estadoFoto = document.getElementById('estado-foto-movil');

    if (contenedorFoto && btnAdjuntarFoto && inputFoto) {
        const esCelular = /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini|Mobi/i.test(navigator.userAgent)
            || navigator.userAgentData?.mobile === true;

        if (esCelular) {
            contenedorFoto.classList.remove('hidden');
        }

        btnAdjuntarFoto.addEventListener('click', () => inputFoto.click());

        inputFoto.addEventListener('change', async () => {
            const archivo = inputFoto.files?.[0];
            if (!archivo) {
                if (estadoFoto) {
                    estadoFoto.classList.add('hidden');
                    estadoFoto.textContent = '';
                }
                return;
            }

            const form = inputFoto.closest('form');
            const submitBtn = form?.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
            }

            try {
                const bitmap = await createImageBitmap(archivo);
                const maxDim = 1600;
                const escala = Math.min(1, maxDim / Math.max(bitmap.width, bitmap.height));
                const canvas = document.createElement('canvas');
                canvas.width = Math.max(1, Math.round(bitmap.width * escala));
                canvas.height = Math.max(1, Math.round(bitmap.height * escala));
                canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);
                bitmap.close?.();

                const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/webp', 0.8));
                if (blob) {
                    const archivoWebp = new File([blob], 'problema.webp', {
                        type: 'image/webp',
                        lastModified: Date.now(),
                    });
                    const dt = new DataTransfer();
                    dt.items.add(archivoWebp);
                    inputFoto.files = dt.files;
                }

                if (estadoFoto) {
                    estadoFoto.textContent = 'Foto comprimida y lista (.webp)';
                    estadoFoto.classList.remove('hidden');
                }
            } catch {
                if (estadoFoto) {
                    estadoFoto.textContent = 'Foto adjuntada';
                    estadoFoto.classList.remove('hidden');
                }
            } finally {
                if (submitBtn && form?.dataset.submitting !== 'true') {
                    submitBtn.disabled = false;
                }
            }
        });
    }
});
