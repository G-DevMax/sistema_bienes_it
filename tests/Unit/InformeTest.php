<?php

namespace Tests\Unit;

use App\Models\Informes\detalles_problemas;
use App\Models\Informes\informes;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Tests\TestCase;

class InformeTest extends TestCase
{
    public function test_buscador_universal_consulta_por_funcionario_y_detalle(): void
    {
        $query = informes::query()->buscadorUniversal('Ethernet');
        $sql = $query->toSql();

        $this->assertStringContainsString('funcionarios', $sql);
        $this->assertStringContainsString('nombre', $sql);
        $this->assertStringContainsString('detalles_problemas', $sql);
        $this->assertStringContainsString('detalle', $sql);
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
        $modelo = new informes();

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
}
