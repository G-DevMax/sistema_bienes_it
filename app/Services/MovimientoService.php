<?php

namespace App\Services;

use App\Models\estados;
use App\Models\items;
use App\Models\movimientos;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Centraliza toda la logica que afecta el stock de los items.
 *
 * Invariante que se mantiene siempre:
 *   stock_total = stock_disponible + prestados_pendientes + transferidos_pendientes
 *
 * Cada operacion corre dentro de una transaccion y bloquea la fila del item
 * (lockForUpdate) para evitar que dos usuarios entreguen la misma unidad a la vez.
 */
class MovimientoService
{
    /**
     * Crea un item nuevo y registra su ingreso inicial de stock.
     */
    public function crearItem(array $datos, int $userId): items
    {
        return DB::transaction(function () use ($datos, $userId) {
            $codigo = trim((string) ($datos['codigo_patrimonial'] ?? '')) ?: null;
            $serializado = $codigo !== null;
            $cantidad = $serializado ? 1 : max(1, (int) ($datos['stock'] ?? 1));

            $item = items::create([
                'categoria_id' => $datos['categoria_id'],
                'descripcion' => $datos['descripcion'],
                'codigo_patrimonial' => $codigo,
                'stock_total' => $cantidad,
                'stock_disponible' => $cantidad,
                'estado_id' => $serializado ? estados::DISPONIBLE : estados::NO_APLICA,
            ]);

            movimientos::create([
                'item_id' => $item->id,
                'user_id' => $userId,
                'tipo_movimiento' => movimientos::INGRESO,
                'fecha_movimiento' => now(),
                'cantidad' => $cantidad,
                'observaciones' => 'Alta inicial del item',
            ]);

            return $item;
        });
    }

    /**
     * Suma unidades a un item generico (compra, donacion, etc.).
     */
    public function ingresarStock(int $itemId, int $cantidad, int $userId, ?string $observaciones = null): movimientos
    {
        return DB::transaction(function () use ($itemId, $cantidad, $userId, $observaciones) {
            $item = items::lockForUpdate()->findOrFail($itemId);

            if ($item->esSerializado()) {
                $this->error('cantidad', 'Los items con código patrimonial son únicos; registrá un item nuevo para cada unidad.');
            }

            $item->stock_total += $cantidad;
            $item->stock_disponible += $cantidad;
            $item->save();

            return movimientos::create([
                'item_id' => $item->id,
                'user_id' => $userId,
                'tipo_movimiento' => movimientos::INGRESO,
                'fecha_movimiento' => now(),
                'cantidad' => $cantidad,
                'observaciones' => $observaciones,
            ]);
        });
    }

    /**
     * Prestamo o transferencia a un funcionario: resta del stock disponible.
     */
    public function registrarEntrega(array $datos, int $userId): movimientos
    {
        return DB::transaction(function () use ($datos, $userId) {
            $item = items::lockForUpdate()->findOrFail($datos['item_id']);
            $tipo = $datos['tipo_movimiento'];
            $cantidad = $this->validarCantidadDisponible($item, (int) $datos['cantidad']);

            $item->stock_disponible -= $cantidad;
            if ($item->esSerializado()) {
                $item->estado_id = $tipo === movimientos::PRESTAMO ? estados::PRESTADO : estados::TRANSFERIDO;
            }
            $item->save();

            return movimientos::create([
                'item_id' => $item->id,
                'funcionario_id' => $datos['funcionario_id'],
                'user_id' => $userId,
                'tipo_movimiento' => $tipo,
                'fecha_movimiento' => $datos['fecha_movimiento'],
                'fecha_devolucion_prevista' => $tipo === movimientos::PRESTAMO ? $datos['fecha_devolucion_prevista'] : null,
                'cantidad' => $cantidad,
                'observaciones' => $datos['observaciones'] ?? null,
            ]);
        });
    }

    /**
     * Baja de unidades que estan fisicamente en Informatica.
     */
    public function registrarBaja(array $datos, int $userId): movimientos
    {
        return DB::transaction(function () use ($datos, $userId) {
            $item = items::lockForUpdate()->findOrFail($datos['item_id']);
            $cantidad = $this->validarCantidadDisponible($item, (int) $datos['cantidad']);

            $item->stock_disponible -= $cantidad;
            $item->stock_total -= $cantidad;
            if ($item->esSerializado()) {
                $item->estado_id = estados::INSERVIBLE;
            }
            $item->save();

            return movimientos::create([
                'item_id' => $item->id,
                'user_id' => $userId,
                'tipo_movimiento' => movimientos::BAJA,
                'fecha_movimiento' => $datos['fecha_movimiento'],
                'cantidad' => $cantidad,
                'motivo' => $datos['motivo'],
                'observaciones' => $datos['observaciones'] ?? null,
            ]);
        });
    }

    /**
     * Devolucion (total o parcial) de un prestamo/transferencia: vuelve al stock disponible.
     */
    public function registrarDevolucion(int $origenId, array $datos, int $userId): movimientos
    {
        return DB::transaction(function () use ($origenId, $datos, $userId) {
            [$origen, $item, $cantidad] = $this->prepararCierreDeEntrega($origenId, (int) $datos['cantidad']);

            $item->stock_disponible += $cantidad;
            if ($item->esSerializado()) {
                $item->estado_id = estados::DISPONIBLE;
            }
            $item->save();

            return movimientos::create([
                'item_id' => $item->id,
                'funcionario_id' => $origen->funcionario_id,
                'user_id' => $userId,
                'tipo_movimiento' => movimientos::DEVOLUCION,
                'movimiento_origen_id' => $origen->id,
                'fecha_movimiento' => $datos['fecha_movimiento'],
                'cantidad' => $cantidad,
                'observaciones' => $datos['observaciones'] ?? null,
            ]);
        });
    }

    /**
     * Baja de unidades que estan en manos de un funcionario (se rompio/perdio).
     * Requiere observaciones que dejen clara la responsabilidad.
     */
    public function registrarBajaDesdeEntrega(int $origenId, array $datos, int $userId): movimientos
    {
        return DB::transaction(function () use ($origenId, $datos, $userId) {
            if (trim((string) ($datos['observaciones'] ?? '')) === '') {
                $this->error('observaciones', 'Para dar de baja un item entregado a un funcionario es obligatorio explicar qué ocurrió y quién es responsable.');
            }

            [$origen, $item, $cantidad] = $this->prepararCierreDeEntrega($origenId, (int) $datos['cantidad']);

            // las unidades no estaban en Informatica, por eso solo baja el total
            $item->stock_total -= $cantidad;
            if ($item->esSerializado()) {
                $item->estado_id = estados::INSERVIBLE;
            }
            $item->save();

            return movimientos::create([
                'item_id' => $item->id,
                'funcionario_id' => $origen->funcionario_id,
                'user_id' => $userId,
                'tipo_movimiento' => movimientos::BAJA,
                'movimiento_origen_id' => $origen->id,
                'fecha_movimiento' => $datos['fecha_movimiento'],
                'cantidad' => $cantidad,
                'motivo' => $datos['motivo'],
                'observaciones' => $datos['observaciones'],
            ]);
        });
    }

    /**
     * Anula un movimiento revirtiendo su efecto sobre el stock. Nunca se elimina.
     */
    public function anular(int $movimientoId, string $motivo, int $userId): movimientos
    {
        return DB::transaction(function () use ($movimientoId, $motivo, $userId) {
            $mov = movimientos::lockForUpdate()->findOrFail($movimientoId);

            if ($mov->estaAnulado()) {
                $this->error('motivo_anulacion', 'El movimiento ya se encuentra anulado.');
            }

            $item = items::lockForUpdate()->findOrFail($mov->item_id);
            $c = $mov->cantidad;

            switch ($mov->tipo_movimiento) {
                case movimientos::INGRESO:
                    if ($item->esSerializado()) {
                        $this->error('motivo_anulacion', 'No se puede anular el alta de un item serializado.');
                    }
                    if ($item->stock_disponible < $c) {
                        $this->error('motivo_anulacion', "No hay {$c} unidades disponibles en Informática para revertir este ingreso.");
                    }
                    $item->stock_total -= $c;
                    $item->stock_disponible -= $c;
                    break;

                case movimientos::PRESTAMO:
                case movimientos::TRANSFERENCIA:
                    if ($mov->hijos()->activos()->exists()) {
                        $this->error('motivo_anulacion', 'Este movimiento tiene devoluciones o bajas registradas. Anulá esas primero.');
                    }
                    $item->stock_disponible += $c;
                    if ($item->esSerializado()) {
                        $item->estado_id = estados::DISPONIBLE;
                    }
                    break;

                case movimientos::DEVOLUCION:
                    if ($item->stock_disponible < $c) {
                        $this->error('motivo_anulacion', 'Las unidades devueltas ya no están disponibles (fueron entregadas o dadas de baja nuevamente).');
                    }
                    $item->stock_disponible -= $c;
                    if ($item->esSerializado()) {
                        $item->estado_id = $this->estadoSegunEntrega($mov->origen);
                    }
                    break;

                case movimientos::BAJA:
                    $item->stock_total += $c;
                    if ($mov->movimiento_origen_id) {
                        // vuelve a quedar en manos del funcionario
                        if ($item->esSerializado()) {
                            $item->estado_id = $this->estadoSegunEntrega($mov->origen);
                        }
                    } else {
                        $item->stock_disponible += $c;
                        if ($item->esSerializado()) {
                            $item->estado_id = estados::DISPONIBLE;
                        }
                    }
                    break;
            }

            $item->save();

            $mov->update([
                'anulado_at' => now(),
                'anulado_por' => $userId,
                'motivo_anulacion' => $motivo,
            ]);

            return $mov;
        });
    }

    /**
     * Devuelve, por item, cuantas unidades hay prestadas, transferidas y dadas de baja.
     * Se resuelve con una sola consulta para toda la pagina de items.
     *
     * @return array<int, array{prestados:int, transferidos:int, inservibles:int}>
     */
    public function resumenPorItem(Collection $itemIds): array
    {
        $resumen = [];
        foreach ($itemIds as $id) {
            $resumen[$id] = ['prestados' => 0, 'transferidos' => 0, 'inservibles' => 0];
        }
        if ($itemIds->isEmpty()) {
            return $resumen;
        }

        $resueltos = DB::table('movimientos')
            ->select('movimiento_origen_id', DB::raw('SUM(cantidad) as resuelto'))
            ->whereNull('anulado_at')
            ->whereNotNull('movimiento_origen_id')
            ->groupBy('movimiento_origen_id');

        $pendientes = DB::table('movimientos as m')
            ->leftJoinSub($resueltos, 'h', 'h.movimiento_origen_id', '=', 'm.id')
            ->whereNull('m.anulado_at')
            ->whereIn('m.tipo_movimiento', [movimientos::PRESTAMO, movimientos::TRANSFERENCIA])
            ->whereIn('m.item_id', $itemIds)
            ->groupBy('m.item_id', 'm.tipo_movimiento')
            ->select('m.item_id', 'm.tipo_movimiento', DB::raw('SUM(m.cantidad - COALESCE(h.resuelto, 0)) as pendiente'))
            ->get();

        foreach ($pendientes as $fila) {
            $clave = $fila->tipo_movimiento === movimientos::PRESTAMO ? 'prestados' : 'transferidos';
            $resumen[$fila->item_id][$clave] = (int) $fila->pendiente;
        }

        $bajas = DB::table('movimientos')
            ->whereNull('anulado_at')
            ->where('tipo_movimiento', movimientos::BAJA)
            ->whereIn('item_id', $itemIds)
            ->groupBy('item_id')
            ->select('item_id', DB::raw('SUM(cantidad) as total'))
            ->get();

        foreach ($bajas as $fila) {
            $resumen[$fila->item_id]['inservibles'] = (int) $fila->total;
        }

        return $resumen;
    }

    // ----------------------------------------------------------------- helpers

    private function validarCantidadDisponible(items $item, int $cantidad): int
    {
        if ($item->esSerializado()) {
            if ($item->estado_id !== estados::DISPONIBLE || $item->stock_disponible < 1) {
                $this->error('item_id', "El item {$item->codigo_patrimonial} no está disponible en Informática.");
            }
            return 1;
        }

        if ($cantidad < 1) {
            $this->error('cantidad', 'La cantidad debe ser al menos 1.');
        }
        if ($cantidad > $item->stock_disponible) {
            $this->error('cantidad', "Stock insuficiente: solo hay {$item->stock_disponible} unidad(es) disponible(s) de \"{$item->descripcion}\".");
        }

        return $cantidad;
    }

    /**
     * Valida y bloquea el prestamo/transferencia original antes de una devolucion o baja.
     *
     * @return array{0: movimientos, 1: items, 2: int}
     */
    private function prepararCierreDeEntrega(int $origenId, int $cantidad): array
    {
        $origen = movimientos::lockForUpdate()->findOrFail($origenId);

        if (!$origen->esEntrega() || $origen->estaAnulado()) {
            $this->error('cantidad', 'Solo se pueden cerrar préstamos o transferencias vigentes.');
        }

        $pendiente = $origen->cantidadPendiente();
        if ($pendiente < 1) {
            $this->error('cantidad', 'Este movimiento ya fue devuelto en su totalidad.');
        }

        $item = items::lockForUpdate()->findOrFail($origen->item_id);
        if ($item->esSerializado()) {
            $cantidad = 1;
        }
        if ($cantidad < 1 || $cantidad > $pendiente) {
            $this->error('cantidad', "La cantidad debe estar entre 1 y {$pendiente} (pendiente de devolución).");
        }

        return [$origen, $item, $cantidad];
    }

    private function estadoSegunEntrega(?movimientos $origen): int
    {
        return $origen && $origen->tipo_movimiento === movimientos::TRANSFERENCIA
            ? estados::TRANSFERIDO
            : estados::PRESTADO;
    }

    private function error(string $campo, string $mensaje): never
    {
        throw ValidationException::withMessages([$campo => $mensaje]);
    }
}
