<?php

namespace App\Http\Controllers\Informes;

use App\Models\Informes\detalles_soluciones;

class DetalleSolucionController extends AbstractCatalogoProblemaController
{
    protected string $modelo = detalles_soluciones::class;

    protected string $tabla = 'detalles_soluciones';

    protected string $campoTexto = 'solucion';

    protected string $vistaPrefix = 'Informes.detalles_soluciones';

    protected string $rutaPrefix = 'detalles-soluciones';

    protected string $variableListado = 'soluciones';

    protected string $variableItem = 'solucion';

    protected string $etiqueta = 'Solución común';

    protected string $etiquetaError = 'solución común';

    protected string $articulo = 'la';

    protected string $terminacionGenero = 'a';

    protected string $mensajeRequired = 'La descripción de la solución es obligatoria.';

    protected string $mensajeMax = 'La solución no puede superar los 255 caracteres.';

    protected string $mensajeUnique = 'Ya existe esa solución registrada para el tipo de problema seleccionado.';

    protected string $mensajePalabras = 'La solución común no puede superar las 100 palabras.';

    protected string $mensajeEnUso = 'No se puede eliminar la solución porque está siendo utilizada en informes o tickets.';
}
