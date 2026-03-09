<?php

namespace App\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\ConnectionException as DatabaseConnectionException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Client\ConnectionException as HttpConnectionException;
use Illuminate\Http\Client\RequestException as HttpRequestException;
use Illuminate\Session\TokenMismatchException;
use PDOException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ErrorTranslator
{
    /**
     * @return array{
     *     status:int,
     *     level:string,
     *     title:string,
     *     message:string,
     *     reason:string,
     *     next_steps:string
     * }
     */
    public function translate(Throwable $exception): array
    {
        if ($exception instanceof AuthorizationException) {
            return $this->fromHttpStatus(403);
        }

        if ($exception instanceof TokenMismatchException) {
            return $this->fromHttpStatus(419);
        }

        if ($exception instanceof HttpExceptionInterface) {
            return $this->fromHttpStatus($exception->getStatusCode(), $exception);
        }

        if ($exception instanceof QueryException) {
            return $this->fromQueryException($exception);
        }

        if ($exception instanceof PDOException) {
            return $this->fromPdoException($exception);
        }

        if (
            $exception instanceof DatabaseConnectionException
            || $exception instanceof HttpConnectionException
            || $exception instanceof HttpRequestException
            || $this->looksLikeInfrastructureFailure($exception)
        ) {
            return $this->infrastructureError();
        }

        if ($this->looksLikeTimeout($exception)) {
            return $this->timeoutError();
        }

        return $this->genericError();
    }

    /**
     * @return array{
     *     status:int,
     *     level:string,
     *     title:string,
     *     message:string,
     *     reason:string,
     *     next_steps:string
     * }
     */
    public function fromHttpStatus(int $status, ?Throwable $exception = null): array
    {
        return match ($status) {
            403 => $this->package(
                403,
                'No tiene permisos para esta accion',
                'No se pudo completar la operacion porque su usuario no tiene permisos.',
                'Puede tratarse de una restriccion por rol o por alcance institucional.',
                'Verifique sus permisos o solicite acceso al administrador del sistema.'
            ),
            404 => $this->package(
                404,
                'No encontramos lo que esta buscando',
                'El recurso solicitado no existe o ya no esta disponible.',
                'La URL puede ser incorrecta o el registro pudo haber sido eliminado.',
                'Regrese al panel y vuelva a buscar el registro desde los modulos principales.'
            ),
            419 => $this->package(
                419,
                'La sesion vencio',
                'La operacion no pudo completarse porque su sesion ya no es valida.',
                'Esto suele ocurrir por inactividad prolongada o por seguridad del navegador.',
                'Ingrese nuevamente al sistema y repita la accion.'
            ),
            429 => $this->package(
                429,
                'Demasiados intentos',
                'Se detectaron demasiadas operaciones en poco tiempo.',
                'El sistema aplica un limite temporal para proteger la plataforma.',
                'Espere unos segundos y vuelva a intentar.'
            ),
            503 => $this->package(
                503,
                'Servicio temporalmente no disponible',
                'El sistema no pudo completar la operacion debido a un problema tecnico temporal.',
                'Puede haber tareas de mantenimiento o una dependencia sin disponibilidad.',
                'Intente nuevamente en unos minutos.'
            ),
            422, 409 => $this->logicOrBusinessError($status, $exception),
            default => $status >= 500
                ? $this->genericError($status)
                : $this->package(
                    $status,
                    'No fue posible completar la solicitud',
                    'La operacion no pudo ser procesada.',
                    'Se recibieron datos incompletos o en un estado no permitido.',
                    'Revise los datos ingresados y vuelva a intentar.',
                    'warning'
                ),
        };
    }

    /**
     * @return array{
     *     status:int,
     *     level:string,
     *     title:string,
     *     message:string,
     *     reason:string,
     *     next_steps:string
     * }
     */
    private function fromQueryException(QueryException $exception): array
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);

        if ($sqlState === '23503' || in_array($driverCode, [1451, 1452], true)) {
            return $this->package(
                409,
                'No se puede completar la operacion',
                'No se puede eliminar este registro porque esta siendo utilizado en otro modulo.',
                'Existen registros relacionados que dependen de este dato.',
                'Primero reasigne o elimine los registros asociados y luego vuelva a intentar.'
            );
        }

        if ($sqlState === '23505' || $driverCode === 1062) {
            return $this->package(
                409,
                'Registro duplicado',
                'Ya existe un registro con estos datos.',
                'El sistema detecto un valor que debe ser unico.',
                'Modifique el dato duplicado y vuelva a guardar.'
            );
        }

        if ($sqlState === '23502' || $driverCode === 1048) {
            return $this->package(
                422,
                'Faltan datos obligatorios',
                'No se pudo guardar porque faltan datos requeridos.',
                'Uno o mas campos obligatorios no fueron completados.',
                'Revise el formulario, complete los campos marcados y vuelva a intentar.'
            );
        }

        if ($sqlState === '22001' || $driverCode === 1406) {
            return $this->package(
                422,
                'Algunos datos son demasiado largos',
                'No se pudo guardar porque uno o mas valores superan la longitud permitida.',
                'El sistema limita la cantidad de caracteres en algunos campos.',
                'Acorte el texto en los campos indicados y vuelva a intentar.'
            );
        }

        if (in_array($sqlState, ['08001', '08003', '08004', '08006', '57P01'], true)) {
            return $this->infrastructureError();
        }

        if ($sqlState === '57014' || $this->looksLikeTimeout($exception)) {
            return $this->timeoutError();
        }

        return $this->package(
            500,
            'Error al acceder a los datos',
            'El sistema no pudo completar la operacion en este momento.',
            'Se produjo un error tecnico al procesar la informacion.',
            'Intente nuevamente. Si el problema persiste, contacte al equipo tecnico.'
        );
    }

    /**
     * @return array{
     *     status:int,
     *     level:string,
     *     title:string,
     *     message:string,
     *     reason:string,
     *     next_steps:string
     * }
     */
    private function fromPdoException(PDOException $exception): array
    {
        $sqlState = (string) $exception->getCode();

        if (in_array($sqlState, ['08001', '08003', '08004', '08006', '57P01'], true)) {
            return $this->infrastructureError();
        }

        if ($this->looksLikeTimeout($exception)) {
            return $this->timeoutError();
        }

        return $this->genericError();
    }

    /**
     * @return array{
     *     status:int,
     *     level:string,
     *     title:string,
     *     message:string,
     *     reason:string,
     *     next_steps:string
     * }
     */
    private function logicOrBusinessError(int $status, ?Throwable $exception): array
    {
        $message = trim((string) ($exception?->getMessage() ?? ''));

        if ($message !== '' && ! $this->looksTechnical($message)) {
            return $this->package(
                $status,
                'Operacion no permitida',
                $message,
                'La accion solicitada no es valida para el estado actual del registro.',
                'Revise la informacion relacionada y vuelva a intentar.'
            );
        }

        return $this->package(
            $status,
            'No se pudo completar la operacion',
            'La accion solicitada no puede realizarse en este momento.',
            'El registro puede tener restricciones de negocio o dependencias activas.',
            'Revise los datos asociados y vuelva a intentar.'
        );
    }

    /**
     * @return array{
     *     status:int,
     *     level:string,
     *     title:string,
     *     message:string,
     *     reason:string,
     *     next_steps:string
     * }
     */
    private function genericError(int $status = 500): array
    {
        return $this->package(
            $status,
            'Algo salio mal',
            'El sistema no pudo completar la operacion.',
            'Puede deberse a un problema temporal o a una condicion no esperada.',
            'Intente nuevamente. Si continua, contacte al administrador del sistema.'
        );
    }

    /**
     * @return array{
     *     status:int,
     *     level:string,
     *     title:string,
     *     message:string,
     *     reason:string,
     *     next_steps:string
     * }
     */
    private function infrastructureError(): array
    {
        return $this->package(
            503,
            'Problema tecnico temporal',
            'El sistema no pudo completar la operacion debido a un problema tecnico temporal.',
            'Puede tratarse de una falla de conexion, una dependencia externa o mantenimiento.',
            'Espere unos minutos e intente nuevamente.'
        );
    }

    /**
     * @return array{
     *     status:int,
     *     level:string,
     *     title:string,
     *     message:string,
     *     reason:string,
     *     next_steps:string
     * }
     */
    private function timeoutError(): array
    {
        return $this->package(
            503,
            'La operacion tardo demasiado',
            'El sistema no pudo completar la operacion en el tiempo esperado.',
            'Puede existir alta carga o una dependencia sin respuesta.',
            'Intente nuevamente en unos minutos.'
        );
    }

    private function looksLikeTimeout(Throwable $exception): bool
    {
        $message = mb_strtolower($exception->getMessage());

        return str_contains($message, 'timeout')
            || str_contains($message, 'timed out')
            || str_contains($message, 'curl error 28')
            || str_contains($message, 'query_wait_timeout');
    }

    private function looksLikeInfrastructureFailure(Throwable $exception): bool
    {
        $message = mb_strtolower($exception->getMessage());

        return str_contains($message, 'connection refused')
            || str_contains($message, 'connection reset')
            || str_contains($message, 'could not connect')
            || str_contains($message, 'server has gone away')
            || str_contains($message, 'no route to host')
            || str_contains($message, 'name or service not known');
    }

    private function looksTechnical(string $message): bool
    {
        $needle = mb_strtolower($message);

        return str_contains($needle, 'sqlstate')
            || str_contains($needle, 'stack trace')
            || str_contains($needle, 'exception')
            || str_contains($needle, 'vendor/')
            || str_contains($needle, 'select ')
            || str_contains($needle, 'insert ')
            || str_contains($needle, 'update ')
            || str_contains($needle, 'delete ');
    }

    /**
     * @return array{
     *     status:int,
     *     level:string,
     *     title:string,
     *     message:string,
     *     reason:string,
     *     next_steps:string
     * }
     */
    private function package(
        int $status,
        string $title,
        string $message,
        string $reason,
        string $nextSteps,
        string $level = 'error'
    ): array {
        return [
            'status' => $status,
            'level' => $level,
            'title' => $title,
            'message' => $message,
            'reason' => $reason,
            'next_steps' => $nextSteps,
        ];
    }
}
