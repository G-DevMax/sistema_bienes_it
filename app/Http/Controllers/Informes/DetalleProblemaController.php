<?php

namespace App\Http\Controllers\Informes;

use App\Models\Informes\detalles_problemas;

class DetalleProblemaController extends AbstractCatalogoProblemaController
{
    protected string $modelo = detalles_problemas::class;

    protected string $tabla = 'detalles_problemas';

    protected string $campoTexto = 'detalle';

    protected string $vistaPrefix = 'Informes.detalles_problemas';

    protected string $rutaPrefix = 'detalles-problemas';

    protected string $variableListado = 'detalles';

    protected string $variableItem = 'detalle';

    protected string $etiqueta = 'Detalle de problema';

    protected string $etiquetaError = 'detalle de problema';

    protected string $articulo = 'el';

    protected string $terminacionGenero = 'o';

    protected string $mensajeRequired = 'El detalle del problema es obligatorio.';

    protected string $mensajeMax = 'El detalle no puede superar los 255 caracteres.';

    protected string $mensajeUnique = 'Ya existe ese detalle registrado para el tipo de problema seleccionado.';

    protected string $mensajePalabras = 'El detalle del problema no puede superar las 100 palabras.';

    protected string $mensajeEnUso = 'No se puede eliminar el detalle porque está siendo utilizado en informes o tickets.';
}
