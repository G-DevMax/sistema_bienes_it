<?php

namespace Tests\Unit;

use App\Models\Bienes\items;
use App\Models\Bienes\movimientos;
use App\Models\dependencias;
use App\Models\funcionarios;
use App\Services\Bienes\InformeMovimientoService;
use App\Services\Bienes\PlanillaMovimientoService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Tests\TestCase;

class InformeMovimientoTest extends TestCase
{
    public function test_filtro_funcionario_sql_cuando_es_todos(): void
    {
        $funcionario = 'todos';

        $query = movimientos::query()
            ->when($funcionario !== 'todos', fn ($q) => $q->where('funcionario_id', $funcionario));

        $this->assertStringNotContainsString('funcionario_id', $query->toSql());
    }

    public function test_filtro_funcionario_sql_cuando_es_especifico(): void
    {
        $funcionario = '12';

        $query = movimientos::query()
            ->when($funcionario !== 'todos', fn ($q) => $q->where('funcionario_id', $funcionario));

        $this->assertStringContainsString('funcionario_id', $query->toSql());
        $this->assertContains('12', $query->getBindings());
    }

    public function test_validacion_acepta_todos(): void
    {
        $payload = [
            'fecha_desde' => '2026-01-01',
            'fecha_hasta' => '2026-01-10',
            'tipo_movimiento' => 'Prestamo',
            'funcionario' => 'todos',
        ];

        $validator = Validator::make($payload, [
            'fecha_desde' => ['required', 'date'],
            'fecha_hasta' => ['required', 'date', 'after_or_equal:fecha_desde'],
            'tipo_movimiento' => ['required', Rule::in(['Prestamo', 'Transferencia'])],
            'funcionario' => ['required', Rule::when($payload['funcionario'] !== 'todos', ['integer', 'exists:funcionarios,id'])],
        ]);

        $this->assertFalse($validator->fails());
    }

    public function test_validacion_rechaza_funcionario_no_numerico(): void
    {
        $payload = [
            'fecha_desde' => '2026-01-01',
            'fecha_hasta' => '2026-01-10',
            'tipo_movimiento' => 'Prestamo',
            'funcionario' => 'invalido',
        ];

        $validator = Validator::make($payload, [
            'fecha_desde' => ['required', 'date'],
            'fecha_hasta' => ['required', 'date', 'after_or_equal:fecha_desde'],
            'tipo_movimiento' => ['required', Rule::in(['Prestamo', 'Transferencia'])],
            'funcionario' => ['required', Rule::when($payload['funcionario'] !== 'todos', ['integer', 'exists:funcionarios,id'])],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('funcionario', $validator->errors()->toArray());
    }

    public function test_servicios_excel_escriben_textos_con_igual_como_string_explicito(): void
    {
        $dependencia = new dependencias(['nombre' => '=DEP(1)']);
        $funcionario = new funcionarios(['nombre' => '=USER(1)']);
        $funcionario->setRelation('dependencia', $dependencia);

        $item = new items([
            'descripcion' => '=SUM(A1:A2)',
            'codigo_patrimonial' => '=PAT01',
        ]);

        $movimiento = new movimientos([
            'tipo_movimiento' => movimientos::PRESTAMO,
            'fecha_movimiento' => now(),
            'cantidad' => 1,
            'motivo' => '=MOTIVO()',
            'observaciones' => '=CMD|calc',
        ]);
        $movimiento->id = 99;
        $movimiento->setRelation('funcionario', $funcionario);
        $movimiento->setRelation('item', $item);
        $movimiento->setRelation('origen', null);
        $movimiento->setRelation('hijos', collect());

        $hojaInforme = app(InformeMovimientoService::class)->generar(collect([$movimiento]))->getSheet(0);
        $this->assertSame(DataType::TYPE_STRING, $hojaInforme->getCell('A2')->getDataType());
        $this->assertSame('=USER(1)', $hojaInforme->getCell('A2')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $hojaInforme->getCell('B2')->getDataType());
        $this->assertSame('=SUM(A1:A2)', $hojaInforme->getCell('B2')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $hojaInforme->getCell('D2')->getDataType());
        $this->assertSame('=DEP(1)', $hojaInforme->getCell('D2')->getValue());

        $hojaPlanilla = app(PlanillaMovimientoService::class)->generar(collect([$movimiento]))->getSheet(0);
        $this->assertSame(DataType::TYPE_STRING, $hojaPlanilla->getCell('G12')->getDataType());
        $this->assertSame('=DEP(1)', $hojaPlanilla->getCell('G12')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $hojaPlanilla->getCell('D15')->getDataType());
        $this->assertSame('=SUM(A1:A2)', $hojaPlanilla->getCell('D15')->getValue());
    }
}
