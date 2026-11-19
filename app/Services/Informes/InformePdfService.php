<?php

namespace App\Services\Informes;

use App\Models\Informes\informes;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfInstance;

/**
 * Genera el PDF "INFORME DE SERVICIO TÉCNICO" capturando primero todos los
 * datos del informe para renderizar la plantilla con ajuste automático de tablas.
 */
class InformePdfService
{
    public function nombreArchivo(informes $informe): string
    {
        return sprintf(
            'Informe_Servicio_Tecnico_%04d_%s.pdf',
            $informe->id,
            $informe->created_at?->format('dmY') ?? now()->format('dmY')
        );
    }

    /**
     * Captura la información del informe y genera el documento PDF en tamaño A4.
     *
     * @return array{numero: int, fecha: string, usuario: string, tecnico: string, titulo: string, tipo_problema: string, detalle: string, observacion: string, logoBase64: string|null}
     */
    public function obtenerDatos(informes $informe): array
    {
        $informe->loadMissing(['funcionario.dependencia', 'detalle', 'tecnico']);

        return [
            'numero' => $informe->id,
            'fecha' => $informe->created_at?->format('d/m/Y') ?? now()->format('d/m/Y'),
            'usuario' => $informe->funcionario?->nombre ?? '—',
            'tecnico' => $informe->tecnico?->name ?? '—',
            'titulo' => $this->formatearTitulo($informe),
            'tipo_problema' => $informe->tipo_problema,
            'detalle' => $informe->detalleTexto(),
            'observacion' => $informe->observacion ?: 'Sin observaciones registradas.',
            'logoBase64' => $this->logoBase64(),
        ];
    }

    private function formatearTitulo(informes $informe): string
    {
        $titulo = trim((string) $informe->titulo);

        if ($informe->tipo_problema === 'Asistencia a Reunión') {
            $titulo = preg_replace(
                '/[ \t]*\R?[ \t]*(Fecha:|Horario aproximado:|Detalles de la reunión:)/u',
                "\n$1",
                $titulo
            ) ?? $titulo;
        }

        return $titulo;
    }

    public function generar(informes $informe): DomPdfInstance
    {
        $datos = $this->obtenerDatos($informe);

        return Pdf::loadView('Informes.pdf', $datos)->setPaper('a4', 'portrait');
    }

    private function logoBase64(): ?string
    {
        $ruta = public_path('img/logo_SND_transparente.png');
        if (! is_file($ruta)) {
            return null;
        }

        // Recorta los márgenes vacíos superior e inferior del logo (misma proporción que el Word)
        if (function_exists('imagecreatefrompng')) {
            $origen = @imagecreatefrompng($ruta);
            if ($origen !== false) {
                $ancho = imagesx($origen);
                $alto = imagesy($origen);
                $yInicio = (int) round($alto * 0.358);
                $altoRecorte = (int) round($alto * 0.261);

                $recortada = imagecrop($origen, [
                    'x' => 0,
                    'y' => $yInicio,
                    'width' => $ancho,
                    'height' => max(1, $altoRecorte),
                ]);
                imagedestroy($origen);

                if ($recortada !== false) {
                    imagesavealpha($recortada, true);
                    ob_start();
                    imagepng($recortada);
                    $png = ob_get_clean();
                    imagedestroy($recortada);

                    if ($png !== false) {
                        return 'data:image/png;base64,'.base64_encode($png);
                    }
                }
            }
        }

        return 'data:image/png;base64,'.base64_encode((string) file_get_contents($ruta));
    }
}
