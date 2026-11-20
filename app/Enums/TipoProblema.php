<?php

namespace App\Enums;

enum TipoProblema: string
{
    case EquipoDispositivo = 'Equipo/Dispositivo';
    case ProgramaSistema = 'Programa/Sistema';
    case Internet = 'Internet';
    case ImpresoraEscaner = 'Impresora/Escáner';
    case CuentasYAccesos = 'Cuentas y Accesos';
    case AsistenciaAReunion = 'Asistencia a Reunión';
    case Consulta = 'Consulta';
    case Insumos = 'Insumos';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
