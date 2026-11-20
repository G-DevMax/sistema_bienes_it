<?php

namespace Tests\Feature;

use App\Http\Controllers\Controller;
use App\Models\Bienes\categorias;
use App\Models\Bienes\estados;
use App\Models\Bienes\items;
use App\Models\rol;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ErrorHandlingTest extends TestCase
{
    use DatabaseTransactions;

    private function crearControladorConMensajeError(): object
    {
        return new class extends Controller
        {
            public function traducir(\Throwable $e, string $porDefecto): string
            {
                return $this->mensajeError($e, $porDefecto);
            }
        };
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function codigosSqlTipicos(): array
    {
        return [
            'duplicado 1062' => [1062, 'Ya existe un registro con esos datos en el sistema.'],
            'fk padre 1451' => [1451, 'No se puede eliminar o modificar el registro porque está vinculado a otros datos.'],
            'fk hijo 1452' => [1452, 'Uno de los registros relacionados que seleccionó no existe o ya no está disponible.'],
            'truncado 1265' => [1265, 'Uno de los valores ingresados no tiene un formato válido o supera la longitud permitida.'],
            'longitud 1406' => [1406, 'Uno de los valores ingresados no tiene un formato válido o supera la longitud permitida.'],
            'lock timeout 1205' => [1205, 'El sistema está procesando otra operación simultánea. Intente nuevamente.'],
            'deadlock 1213' => [1213, 'El sistema está procesando otra operación simultánea. Intente nuevamente.'],
            'otro codigo' => [9999, 'No se pudo completar la operación.'],
        ];
    }

    #[DataProvider('codigosSqlTipicos')]
    public function test_mensaje_error_traduce_codigos_sql_sin_exponer_consulta(int $codigoMysql, string $esperado): void
    {
        $pdo = new \PDOException('SQLSTATE[23000]: Integrity constraint violation: tabla secreta');
        $pdo->errorInfo = ['23000', $codigoMysql, 'Duplicate entry X in tabla_interna'];

        $queryException = new QueryException(
            'mysql',
            'INSERT INTO `tickets` (`campo`) VALUES (?)',
            ['valor'],
            $pdo
        );

        $resultado = $this->crearControladorConMensajeError()->traducir(
            $queryException,
            'No se pudo completar la operación.'
        );

        $this->assertSame($esperado, $resultado);
        $this->assertStringNotContainsString('SQLSTATE', $resultado);
        $this->assertStringNotContainsString('INSERT INTO', $resultado);
        $this->assertStringNotContainsString('tabla_interna', $resultado);
    }

    public function test_mensaje_error_oculta_excepciones_genericas_y_conserva_validation_exception(): void
    {
        $controlador = $this->crearControladorConMensajeError();

        $generica = new \RuntimeException('Fallo interno en /var/www/secret/file.php con SELECT * FROM users');
        $this->assertSame(
            'No se pudo registrar el movimiento.',
            $controlador->traducir($generica, 'No se pudo registrar el movimiento.')
        );

        $validacion = ValidationException::withMessages([
            'cantidad' => 'Stock insuficiente para completar la entrega.',
        ]);
        $this->assertSame(
            'No se pudo registrar el movimiento: Stock insuficiente para completar la entrega.',
            $controlador->traducir($validacion, 'No se pudo registrar el movimiento.')
        );
    }

    public function test_movimientos_e_ingresar_stock_conservan_mensaje_de_validation_exception_en_http(): void
    {
        $admin = User::withoutEvents(fn (): User => User::factory()->create([
            'rol_id' => rol::ADMIN,
            'estado_user' => 1,
        ]));

        $categoria = categorias::first() ?? categorias::withoutEvents(
            fn (): categorias => categorias::create(['nombre' => 'Categoria Error Test'])
        );

        $itemSerializado = items::withoutEvents(fn (): items => items::create([
            'categoria_id' => $categoria->id,
            'descripcion' => 'Monitor Serializado Test',
            'codigo_patrimonial' => 'PAT-ERR-001',
            'stock_total' => 1,
            'stock_disponible' => 1,
            'estado_id' => estados::DISPONIBLE,
        ]));

        $response = $this->actingAs($admin)
            ->post(route('items.ingresar-stock', $itemSerializado), [
                'cantidad' => 2,
            ]);

        $response->assertRedirect(route('items.index'));
        $response->assertSessionHas('error', fn (string $mensaje): bool => str_contains(
            $mensaje,
            'Los items con código patrimonial son únicos'
        ));
    }
}
