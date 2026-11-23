<?php

namespace App\Services\Bienes;

use App\Models\Bienes\movimientos;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Borders;

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

    public function nombreArchivo(Collection $movimientos): string
    {
        $movimiento = $movimientos->first();

        return sprintf('FC11_Movimiento_%s_%s.xlsx',
            $movimiento->lote_id ? 'Lote_'.substr($movimiento->lote_id, 0, 8) : sprintf('%04d', $movimiento->id),
            $movimiento->fecha_movimiento->format('dmY')
        );
    }

    public function generar(Collection $movimientos): Spreadsheet
    {
        $movimientoPrincipal = $movimientos->first();
        $movimientoPrincipal->loadMissing(['funcionario.dependencia', 'origen.funcionario.dependencia', 'hijos']);

        $reader = IOFactory::createReader('Xls');
        $reader->setLoadSheetsOnly(self::HOJA_PLANTILLA);
        $spreadsheet = $reader->load($this->plantillaPath());
        $hoja = $spreadsheet->getSheet(0);
        $hoja->setTitle($movimientoPrincipal->fecha_movimiento->format('m-Y'));

        // cabecera: numero, fecha y dependencias
        $hoja->setCellValue('J5', $movimientoPrincipal->lote_id ? 'LOTE '.substr($movimientoPrincipal->lote_id, 0, 6) : $movimientoPrincipal->id);
        $hoja->getStyle('J5')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setVertical(Alignment::VERTICAL_TOP);
        $hoja->setCellValue('I9', Date::PHPToExcel($movimientoPrincipal->fecha_movimiento));

        [$remitente, $destinataria] = $this->dependencias($movimientoPrincipal);
        $hoja->setCellValueExplicit('B12', $remitente, DataType::TYPE_STRING);
        $hoja->setCellValueExplicit('G12', $destinataria, DataType::TYPE_STRING);

        // limpia el detalle de ejemplo
        for ($fila = self::FILA_INICIO; $fila <= self::FILA_FIN; $fila++) {
            foreach (['B', 'C', 'D', 'K', 'L', 'M', 'N'] as $col) {
                $hoja->setCellValue($col.$fila, null);
            }
        }

        $fila = self::FILA_INICIO;

        foreach ($movimientos as $movimiento) {
            $movimiento->loadMissing(['item']);
            $item = $movimiento->item;

            // detalle del bien movido
            $hoja->setCellValueExplicit('B'.$fila, $item->esSerializado() ? $item->codigo_patrimonial : 'S/N', DataType::TYPE_STRING);
            $hoja->setCellValue('C'.$fila, $movimiento->cantidad);
            $hoja->setCellValueExplicit('D'.$fila, (string) $item->descripcion, DataType::TYPE_STRING);
            if ($col = self::COLUMNA_MOTIVO[$movimiento->tipo_movimiento] ?? null) {
                $hoja->setCellValue($col.$fila, 'X');
            }
            $fila++;
        }

        // lineas complementarias bajo el bien (usuario, motivo, observaciones) del lote
        $notas = [];
        if ($movimientoPrincipal->funcionario) {
            $notas[] = 'Usuario: '.$movimientoPrincipal->funcionario->nombre;
        }
        if ($movimientoPrincipal->motivo) {
            $notas[] = 'Motivo general: '.$movimientoPrincipal->motivo;
        }
        if ($movimientoPrincipal->observaciones) {
            $notas[] = 'Obs. general: '.$movimientoPrincipal->observaciones;
        }
        foreach ($notas as $nota) {
            if ($fila > self::FILA_FIN) {
                break;
            }
            $hoja->setCellValueExplicit('D'.$fila, $nota, DataType::TYPE_STRING);
            $fila++;
        }

        // marcar el sobrante con una línea para invalidar los espacios vacíos
        if ($fila <= self::FILA_FIN) {
            $hoja->getStyle('B'.$fila.':N'.self::FILA_FIN)
                ->getBorders()
                ->getDiagonal()
                ->setBorderStyle(Border::BORDER_THIN);
            $hoja->getStyle('B'.$fila.':N'.self::FILA_FIN)
                ->getBorders()
                ->setDiagonalDirection(Borders::DIAGONAL_DOWN);
        }

        // pie: fecha de devolucion (real, no la prevista)
        if ($movimientoPrincipal->tipo_movimiento === movimientos::DEVOLUCION) {
            $hoja->setCellValue('B56', 'FECHA DEVOLUCIÓN: '.$movimientoPrincipal->fecha_movimiento->format('d/m/Y'));
        } elseif ($movimientoPrincipal->tipo_movimiento === movimientos::PRESTAMO) {
            $ultimaDevolucion = $movimientoPrincipal->hijos->where('tipo_movimiento', movimientos::DEVOLUCION)->sortByDesc('fecha_movimiento')->first();
            if ($ultimaDevolucion) {
                $hoja->setCellValue('B56', 'FECHA DEVOLUCIÓN: '.$ultimaDevolucion->fecha_movimiento->format('d/m/Y'));
            }
        } elseif ($movimientoPrincipal->tipo_movimiento === movimientos::TRANSFERENCIA) {
            // Eliminar el cuadro de devolución y firma para transferencias
            $hoja->setCellValue('B56', '');
            $hoja->setCellValue('B57', '');
            $hoja->setCellValue('B61', '');
            $hoja->getStyle('A55:N62')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_NONE);
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
