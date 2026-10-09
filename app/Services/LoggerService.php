<?php

namespace App\Services;

use Illuminate\Broadcasting\Channel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Throwable;

class LoggerService
{
    protected const CHANNEL = 'app_internal_log';

    protected static function formatContext(array $context = []): array
    {
        // contexto base del log con los datos de entorno y el usuario
        return array_merge([
            'id_user' => Auth::id() ?? 'guest', // ID del usuario autenticado
            'name' => Auth::user()->name ?? 'anonymous', // Nombre del usuario autenticado
            'url' => request()->url(), // URL de la solicitud
            'method' => request()->method(), // Método de la solicitud
        ], $context);
    }

    // metodos de los diferentes niveles de log
    public static function info(string $message, array $context = []): void
    {
        // informacion rutinaria
        Log::channel(self::CHANNEL)->info($message, self::formatContext($context));
    }

    public static function warning(string $message, array $context = []): void
    {
        // eventos inesperados pero no criticos
        Log::channel(self::CHANNEL)->warning($message, self::formatContext($context));
    }

    public static function error(string $message, array $context = [], ?Throwable $exception = null): void
    {
        // errores que requieren atencion o excepciones captadas
        if ($exception) {
            $context['exception'] = [
                'error_class' => get_class($exception),
                'error_msg' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine()
            ];
        }

        Log::channel(self::CHANNEL)->error($message, self::formatContext($context));
    }
}
