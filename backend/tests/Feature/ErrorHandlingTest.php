<?php

namespace Tests\Feature;

use App\Services\ErrorTranslator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Route;
use PDOException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ErrorHandlingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.debug', false);

        Route::middleware('web')->get('/_test/error-500', function (): never {
            throw new RuntimeException('Unexpected failure');
        });

        Route::middleware('web')->post('/_test/error-422', function (): never {
            throw new HttpException(422, 'No se puede eliminar esta institucion porque tiene equipos registrados.');
        });
    }

    public function test_it_renders_a_friendly_error_page_for_unexpected_errors(): void
    {
        $response = $this->get('/_test/error-500');

        $response->assertStatus(500);
        $response->assertSee('Algo salio mal');
        $response->assertSee('Que paso');
        $response->assertSee('Volver al panel');
    }

    public function test_it_redirects_back_with_a_friendly_message_for_business_errors_on_post(): void
    {
        $response = $this->withSession(['_token' => 'test-token'])
            ->from('/dashboard')
            ->post('/_test/error-422', ['_token' => 'test-token']);

        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('error', 'No se puede eliminar esta institucion porque tiene equipos registrados.');
    }

    public function test_error_translator_maps_foreign_key_violations_to_friendly_message(): void
    {
        $previous = new PDOException('SQLSTATE[23503]: Foreign key violation', '23503');
        $queryException = new QueryException('delete from institutions where id = ?', [1], $previous);
        $queryException->errorInfo = ['23503', 0, 'Foreign key violation'];

        /** @var ErrorTranslator $translator */
        $translator = app(ErrorTranslator::class);
        $translated = $translator->translate($queryException);

        $this->assertSame(409, $translated['status']);
        $this->assertSame('No se puede eliminar este registro porque esta siendo utilizado en otro modulo.', $translated['message']);
    }
}
