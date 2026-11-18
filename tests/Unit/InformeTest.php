<?php

namespace Tests\Unit;

use App\Models\dependencias;
use App\Models\funcionarios;
use App\Models\Informes\detalles_problemas;
use App\Models\Informes\informes;
use App\Services\Informes\InformePdfService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\Rule;
use Tests\TestCase;

class InformeTest extends TestCase
{
    public function test_buscador_universal_consulta_por_funcionario_y_titulo(): void
    {
        $query = informes::query()->buscadorUniversal('Ethernet');
        $sql = $query->toSql();

        $this->assertStringContainsString('funcionarios', $sql);
        $this->assertStringContainsString('nombre', $sql);
        $this->assertStringContainsString('titulo', $sql);
        $this->assertSame(['%Ethernet%', '%Ethernet%'], $query->getBindings());
    }

    public function test_detalle_texto_devuelve_otro_o_el_detalle_relacionado(): void
    {
        $sinDetalle = new informes(['detalle_id' => null]);
        $this->assertSame('Otro (especificar)', $sinDetalle->detalleTexto());

        $conDetalle = new informes(['detalle_id' => 1]);
        $conDetalle->setRelation('detalle', new detalles_problemas(['detalle' => 'Sin acceso a red']));
        $this->assertSame('Sin acceso a red', $conDetalle->detalleTexto());
    }

    public function test_created_at_no_es_editable_por_asignacion_masiva(): void
    {
        $modelo = new informes;

        $this->assertNotContains('created_at', $modelo->getFillable());
        $this->assertContains('titulo', $modelo->getFillable());
    }

    public function test_validacion_titulo_hasta_100_palabras(): void
    {
        $reglaPalabras = function (string $attribute, mixed $value, \Closure $fail) {
            $palabras = preg_split('/\s+/u', trim((string) $value), -1, PREG_SPLIT_NO_EMPTY);
            if (count($palabras) > 100) {
                $fail('El título no puede superar las 100 palabras.');
            }
        };

        $valido = Validator::make(
            ['titulo' => implode(' ', array_fill(0, 100, 'ok'))],
            ['titulo' => ['required', 'string', 'max:500', $reglaPalabras]]
        );
        $this->assertFalse($valido->fails());

        $invalido = Validator::make(
            ['titulo' => implode(' ', array_fill(0, 101, 'ok'))],
            ['titulo' => ['required', 'string', 'max:500', $reglaPalabras]]
        );
        $this->assertTrue($invalido->fails());
        $this->assertArrayHasKey('titulo', $invalido->errors()->toArray());
    }

    public function test_validacion_exige_observacion_solo_cuando_es_otro_especificar(): void
    {
        $sinDetalle = Validator::make(
            ['detalle_id' => null, 'observacion' => ''],
            ['observacion' => [Rule::requiredIf(true), 'nullable', 'string']]
        );
        $this->assertTrue($sinDetalle->fails());
        $this->assertArrayHasKey('observacion', $sinDetalle->errors()->toArray());

        $conDetalle = Validator::make(
            ['detalle_id' => 5, 'observacion' => null],
            ['observacion' => [Rule::requiredIf(false), 'nullable', 'string']]
        );
        $this->assertFalse($conDetalle->fails());
    }

    public function test_vistas_informes_se_renderizan_correctamente(): void
    {
        View::share('errors', new ViewErrorBag);

        $dependencia = new dependencias(['nombre' => 'Informática']);
        $funcionario = new funcionarios(['nombre' => 'Juan Pérez']);
        $funcionario->id = 1;
        $funcionario->setRelation('dependencia', $dependencia);

        $detalle = new detalles_problemas(['tipo_problema' => 'Red', 'detalle' => 'Sin acceso a red']);
        $detalle->id = 2;

        $informe = new informes([
            'titulo' => 'Corte de red',
            'funcionario_id' => 1,
            'tipo_problema' => 'Red',
            'detalle_id' => 2,
            'observacion' => 'Se reinició el switch.',
        ]);
        $informe->id = 10;
        $informe->created_at = now();
        $informe->updated_at = now();
        $informe->setRelation('funcionario', $funcionario);
        $informe->setRelation('detalle', $detalle);

        $paginador = new LengthAwarePaginator(collect([$informe]), 1, 10, 1);

        $index = $this->view('Informes.index', [
            'informes' => $paginador,
            'tiposProblema' => informes::TIPOS_PROBLEMA,
        ]);
        $index->assertSee('Crear nuevo informe');
        $index->assertSee('Corte de red');
        $index->assertSee('Juan Pérez');

        $create = $this->view('Informes.create', [
            'funcionarios' => collect([$funcionario]),
            'tiposProblema' => informes::TIPOS_PROBLEMA,
            'detalles' => collect([$detalle]),
            'fechaHora' => now(),
        ]);
        $create->assertSee('Registrar Informe');
        $create->assertSee('type="radio"', false);
        $create->assertSee('Otro (especificar)');

        $edit = $this->view('Informes.edit', [
            'informe' => $informe,
            'funcionarios' => collect([$funcionario]),
            'tiposProblema' => informes::TIPOS_PROBLEMA,
            'detalles' => collect([$detalle]),
            'fechaHora' => $informe->created_at,
        ]);
        $edit->assertSee('Editar Informe #10');
        $edit->assertSee('disabled', false);

        $show = $this->view('Informes.show', [
            'informe' => $informe,
        ]);
        $show->assertSee('Informe #10');
        $show->assertSee('Imprimir Informe');
        $show->assertSee(route('informes.pdf', 10));
        $show->assertSee('Se reinició el switch.');

        $detallesIndex = $this->view('Informes.detalles_problemas.index', [
            'detalles' => new LengthAwarePaginator(collect([$detalle]), 1, 10, 1),
            'tiposProblema' => informes::TIPOS_PROBLEMA,
        ]);
        $detallesIndex->assertSee('Problemas Comunes');
        $detallesIndex->assertSee('Sin acceso a red');

        $detallesCreate = $this->view('Informes.detalles_problemas.create', [
            'tiposProblema' => informes::TIPOS_PROBLEMA,
        ]);
        $detallesCreate->assertSee('Registrar Problema Común');

        $detallesEdit = $this->view('Informes.detalles_problemas.edit', [
            'detalle' => $detalle,
            'tiposProblema' => informes::TIPOS_PROBLEMA,
        ]);
        $detallesEdit->assertSee('Editar Problema Común #2');

        $detalle->informes_count = 3;
        $detallesShow = $this->view('Informes.detalles_problemas.show', [
            'detalle' => $detalle,
        ]);
        $detallesShow->assertSee('Detalle #2');
        $detallesShow->assertSee('Sin acceso a red');
    }

    public function test_buscador_universal_detalles_problemas(): void
    {
        $query = detalles_problemas::query()->buscadorUniversal('Impresora');
        $sql = $query->toSql();

        $this->assertStringContainsString('detalle', $sql);
        $this->assertStringContainsString('tipo_problema', $sql);
        $this->assertSame(['%Impresora%', '%Impresora%'], $query->getBindings());
    }

    public function test_genera_pdf_informe_servicio_tecnico_con_texto_largo(): void
    {
        $service = new InformePdfService;
        $logoMethod = new \ReflectionMethod($service, 'logoBase64');
        $logoBase64 = $logoMethod->invoke($service);

        $this->assertNotNull($logoBase64);
        $this->assertStringStartsWith('data:image/png;base64,', $logoBase64);

        $pdfBytes = Pdf::loadView('Informes.pdf', [
            'numero' => 1,
            'fecha' => '16/11/2026',
            'usuario' => 'Nacho Bogarin',
            'tecnico' => 'Gustavo Martínez',
            'titulo' => implode(' ', array_fill(0, 40, 'Equipo')),
            'tipo_problema' => 'Hardware',
            'detalle' => 'El equipo presenta fallos en su fuente de poder',
            'observacion' => "Se realizó el cambio de fuente de poder.\nEl usuario mandó la petición por correo.",
            'logoBase64' => $logoBase64,
        ])->setPaper('a4', 'portrait')->output();

        $this->assertStringStartsWith('%PDF-', $pdfBytes);
    }
}
