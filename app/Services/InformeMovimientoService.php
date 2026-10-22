<?php

namespace App\Services;

use App\Models\movimientos;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Genera el informe de movimientos de insumos (items de categoria
 * Periferico/Hardware) tomando como base la plantilla informe_movimientos.xlsx.
 * La plantilla trae el encabezado en la fila 1 y el detalle desde la fila 2.
 */
class InformeMovimientoService
{
    private const CATEGORIAS_INSUMO = ['Periferico', 'Hardware'];

    private const FILA_INICIO = 2;

    public function plantillaPath(): string
    {
        return resource_path('plantillas/informe_movimientos.xlsx');
    }

    public function nombreArchivo(string $tipo, string $desde, string $hasta): string
    {
        return sprintf('Informe_Movimientos_%s_%s_%s.xlsx', strtolower(trim($tipo)), Carbon::parse($desde)->format('dmY'), Carbon::parse($hasta)->format('dmY'));
    }

    // movimientos de insumos vigentes del tipo indicado (prestamo/transferencia) dentro del rango de fechas
    public function obtenerMovimientos(string $desde, string $hasta, string $tipo): Collection
    {
        // el valor del formulario ("Prestamo"/"Transferencia") coincide con la BD en minusculas
        $tipo = strtolower(trim($tipo));

        return movimientos::with(['item.categoria', 'funcionario.dependencia'])
            ->whereNull('anulado_at')
            ->where('tipo_movimiento', $tipo)
            ->whereDate('fecha_movimiento', '>=', $desde)
            ->whereDate('fecha_movimiento', '<=', $hasta)
            ->whereHas('item.categoria', fn($q) => $q->whereIn('nombre', self::CATEGORIAS_INSUMO))
            ->orderBy('fecha_movimiento')
            ->orderBy('id')
            ->get();
    }

    public function generar(Collection $movimientos): Spreadsheet
    {
        $spreadsheet = IOFactory::load($this->plantillaPath());
        $hoja = $spreadsheet->getSheet(0);

        // limpia el detalle de ejemplo conservando el estilo de la primera fila
        $ultimaFila = $hoja->getHighestRow();
        $hoja->getStyle('A' . self::FILA_INICIO . ':D' . $ultimaFila);
        for ($fila = self::FILA_INICIO; $fila <= $ultimaFila; $fila++) {
            foreach (['A', 'B', 'C', 'D'] as $col) {
                $hoja->setCellValue($col . $fila, null);
            }
        }

        $fila = self::FILA_INICIO;
        foreach ($movimientos as $movimiento) {
            if ($fila > self::FILA_INICIO) {
                foreach (['A', 'B', 'C', 'D'] as $col) {
                    $hoja->duplicateStyle($hoja->getStyle($col . self::FILA_INICIO), $col . $fila);
                }
            }

            $hoja->setCellValue('A' . $fila, $movimiento->funcionario?->nombre ?? '');
            $hoja->setCellValue('B' . $fila, $movimiento->item?->descripcion ?? '');
            $hoja->setCellValue('C' . $fila, Date::PHPToExcel($movimiento->fecha_movimiento));
            $hoja->setCellValue('D' . $fila, $movimiento->funcionario?->dependencia?->nombre ?? '');
            $fila++;
        }

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }
}
