<?php

namespace App\Services;

use App\Models\movimientos;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Genera la planilla FC 11 "Movimiento Interno de Bienes de Uso" a partir
 * de un movimiento, tomando como base la plantilla original en .xls para
 * conservar logo, combinaciones de celdas, bordes y configuracion de impresion.
 */
class PlanillaMovimientoService
{
    // hoja de la plantilla que se usa como modelo (la mas limpia y vigente)
    private const HOJA_PLANTILLA = '10-2025';

    private const DEPENDENCIA_IT = 'Departamento de Informática y TIC';

    // filas disponibles para detalle de bienes en la plantilla
    private const FILA_INICIO = 15;
    private const FILA_FIN = 45;

    // columnas de la casilla MOTIVO: I=Inservible, P=Prestamo, T=Transferencia, F=Faltante
    private const COLUMNA_MOTIVO = [
        movimientos::PRESTAMO => 'L',
        movimientos::TRANSFERENCIA => 'M',
        movimientos::BAJA => 'N',
    ];

    public function plantillaPath(): string
    {
        return resource_path('plantillas/fc11_planilla_movimiento.xls');
    }

    public function nombreArchivo(\Illuminate\Support\Collection $movimientos): string
    {
        $movimiento = $movimientos->first();
        return sprintf('FC11_Movimiento_%s_%s.xlsx', 
            $movimiento->lote_id ? 'Lote_'.substr($movimiento->lote_id, 0, 8) : sprintf('%04d', $movimiento->id), 
            $movimiento->fecha_movimiento->format('dmY')
        );
    }

    public function generar(\Illuminate\Support\Collection $movimientos): Spreadsheet
    {
        $movimientoPrincipal = $movimientos->first();
        $movimientoPrincipal->loadMissing(['funcionario.dependencia', 'origen.funcionario.dependencia', 'hijos']);

        $reader = IOFactory::createReader('Xls');
        $reader->setLoadSheetsOnly(self::HOJA_PLANTILLA);
        $spreadsheet = $reader->load($this->plantillaPath());
        $hoja = $spreadsheet->getSheet(0);
        $hoja->setTitle($movimientoPrincipal->fecha_movimiento->format('m-Y'));

        // cabecera: numero, fecha y dependencias
        $hoja->setCellValue('J5', $movimientoPrincipal->lote_id ? 'LOTE ' . substr($movimientoPrincipal->lote_id, 0, 6) : $movimientoPrincipal->id);
        $hoja->getStyle('J5')->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT)
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
        $hoja->setCellValue('I9', Date::PHPToExcel($movimientoPrincipal->fecha_movimiento));

        [$remitente, $destinataria] = $this->dependencias($movimientoPrincipal);
        $hoja->setCellValue('B12', $remitente);
        $hoja->setCellValue('G12', $destinataria);

        // limpia el detalle de ejemplo
        for ($fila = self::FILA_INICIO; $fila <= self::FILA_FIN; $fila++) {
            foreach (['B', 'C', 'D', 'K', 'L', 'M', 'N'] as $col) {
                $hoja->setCellValue($col . $fila, null);
            }
        }

        $fila = self::FILA_INICIO;

        foreach ($movimientos as $movimiento) {
            $movimiento->loadMissing(['item']);
            $item = $movimiento->item;

            // detalle del bien movido
            $hoja->setCellValueExplicit('B' . $fila, $item->esSerializado() ? $item->codigo_patrimonial : 'S/N', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $hoja->setCellValue('C' . $fila, $movimiento->cantidad);
            $hoja->setCellValue('D' . $fila, $item->descripcion);
            if ($col = self::COLUMNA_MOTIVO[$movimiento->tipo_movimiento] ?? null) {
                $hoja->setCellValue($col . $fila, 'X');
            }
            $fila++;
        }

        // lineas complementarias bajo el bien (usuario, motivo, observaciones) del lote
        $notas = [];
        if ($movimientoPrincipal->funcionario) {
            $notas[] = 'Usuario: ' . $movimientoPrincipal->funcionario->nombre;
        }
        if ($movimientoPrincipal->motivo) {
            $notas[] = 'Motivo general: ' . $movimientoPrincipal->motivo;
        }
        if ($movimientoPrincipal->observaciones) {
            $notas[] = 'Obs. general: ' . $movimientoPrincipal->observaciones;
        }
        foreach ($notas as $nota) {
            if ($fila > self::FILA_FIN) break;
            $hoja->setCellValue('D' . $fila, $nota);
            $fila++;
        }

        // marcar el sobrante con una línea para invalidar los espacios vacíos
        if ($fila <= self::FILA_FIN) {
            $hoja->getStyle('B' . $fila . ':N' . self::FILA_FIN)
                ->getBorders()
                ->getDiagonal()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $hoja->getStyle('B' . $fila . ':N' . self::FILA_FIN)
                ->getBorders()
                ->setDiagonalDirection(\PhpOffice\PhpSpreadsheet\Style\Borders::DIAGONAL_DOWN);
        }

        // pie: fecha de devolucion (real, no la prevista)
        if ($movimientoPrincipal->tipo_movimiento === movimientos::DEVOLUCION) {
            $hoja->setCellValue('B56', 'FECHA DEVOLUCIÓN: ' . $movimientoPrincipal->fecha_movimiento->format('d/m/Y'));
        } elseif ($movimientoPrincipal->tipo_movimiento === movimientos::PRESTAMO) {
            $ultimaDevolucion = $movimientoPrincipal->hijos->where('tipo_movimiento', movimientos::DEVOLUCION)->sortByDesc('fecha_movimiento')->first();
            if ($ultimaDevolucion) {
                $hoja->setCellValue('B56', 'FECHA DEVOLUCIÓN: ' . $ultimaDevolucion->fecha_movimiento->format('d/m/Y'));
            }
        } elseif ($movimientoPrincipal->tipo_movimiento === movimientos::TRANSFERENCIA) {
            // Eliminar el cuadro de devolución y firma para transferencias
            $hoja->setCellValue('B56', '');
            $hoja->setCellValue('B57', '');
            $hoja->setCellValue('B61', '');
            $hoja->getStyle('A55:N62')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE);
        }

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    // [remitente, destinataria] segun el sentido del movimiento
    private function dependencias(movimientos $movimiento): array
    {
        $dependenciaFuncionario = $movimiento->funcionario?->dependencia?->nombre ?? '';

        return match ($movimiento->tipo_movimiento) {
            // el bien vuelve a Informatica desde la dependencia del funcionario
            movimientos::DEVOLUCION => [
                $movimiento->funcionario?->dependencia?->nombre
                    ?? $movimiento->origen?->funcionario?->dependencia?->nombre
                    ?? '',
                self::DEPENDENCIA_IT,
            ],
            movimientos::BAJA => [
                $movimiento->funcionario?->dependencia?->nombre
                    ?? $movimiento->origen?->funcionario?->dependencia?->nombre
                    ?? self::DEPENDENCIA_IT,
                self::DEPENDENCIA_IT,
            ],
            default => [self::DEPENDENCIA_IT, $dependenciaFuncionario],
        };
    }
}
