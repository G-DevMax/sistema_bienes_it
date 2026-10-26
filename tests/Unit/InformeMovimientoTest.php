<?php

namespace Tests\Unit;

use App\Models\movimientos;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Tests\TestCase;

class InformeMovimientoTest extends TestCase
{
    public function test_filtro_funcionario_sql_cuando_es_todos(): void
    {
        $funcionario = 'todos';

        $query = movimientos::query()
            ->when($funcionario !== 'todos', fn($q) => $q->where('funcionario_id', $funcionario));

        $this->assertStringNotContainsString('funcionario_id', $query->toSql());
    }

    public function test_filtro_funcionario_sql_cuando_es_especifico(): void
    {
        $funcionario = '12';

        $query = movimientos::query()
            ->when($funcionario !== 'todos', fn($q) => $q->where('funcionario_id', $funcionario));

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
}
