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

    public function nombreArchivo(movimientos $movimiento): string
    {
        return sprintf('FC11_Movimiento_%04d_%s.xlsx', $movimiento->id, $movimiento->fecha_movimiento->format('dmY'));
    }

    public function generar(movimientos $movimiento): Spreadsheet
    {
        $movimiento->loadMissing(['item', 'funcionario.dependencia', 'origen.funcionario.dependencia']);

        $reader = IOFactory::createReader('Xls');
        $reader->setLoadSheetsOnly(self::HOJA_PLANTILLA);
        $spreadsheet = $reader->load($this->plantillaPath());
        $hoja = $spreadsheet->getSheet(0);
        $hoja->setTitle($movimiento->fecha_movimiento->format('m-Y'));

        // cabecera: numero, fecha y dependencias
        $hoja->setCellValue('J5', $movimiento->id);
        $hoja->setCellValue('I9', Date::PHPToExcel($movimiento->fecha_movimiento));

        [$remitente, $destinataria] = $this->dependencias($movimiento);
        $hoja->setCellValue('B12', $remitente);
        $hoja->setCellValue('G12', $destinataria);

        // limpia el detalle de ejemplo
        for ($fila = self::FILA_INICIO; $fila <= self::FILA_FIN; $fila++) {
            foreach (['B', 'C', 'D', 'K', 'L', 'M', 'N'] as $col) {
                $hoja->setCellValue($col . $fila, null);
            }
        }

        // detalle del bien movido
        $item = $movimiento->item;
        $fila = self::FILA_INICIO;
        $hoja->setCellValueExplicit('B' . $fila, $item->esSerializado() ? $item->codigo_patrimonial : 'S/N', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $hoja->setCellValue('C' . $fila, $movimiento->cantidad);
        $hoja->setCellValue('D' . $fila, $item->descripcion);
        if ($col = self::COLUMNA_MOTIVO[$movimiento->tipo_movimiento] ?? null) {
            $hoja->setCellValue($col . $fila, 'X');
        }

        // lineas complementarias bajo el bien (usuario, motivo, observaciones)
        $notas = [];
        if ($movimiento->funcionario) {
            $notas[] = 'Usuario: ' . $movimiento->funcionario->nombre;
        }
        if ($movimiento->motivo) {
            $notas[] = 'Motivo: ' . $movimiento->motivo;
        }
        if ($movimiento->observaciones) {
            $notas[] = 'Obs.: ' . $movimiento->observaciones;
        }
        foreach ($notas as $nota) {
            $fila++;
            if ($fila > self::FILA_FIN) {
                break;
            }
            $hoja->setCellValue('D' . $fila, $nota);
        }

        // pie: fecha de devolucion prevista (prestamos)
        if ($movimiento->tipo_movimiento === movimientos::PRESTAMO && $movimiento->fecha_devolucion_prevista) {
            $hoja->setCellValue('B56', 'FECHA DEVOLUCIÓN: ' . $movimiento->fecha_devolucion_prevista->format('d/m/Y'));
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
