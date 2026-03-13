<?php

namespace App\Services;

use App\Models\Acta;
use App\Models\Equipo;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ActaPdfDataService
{
    /**
     * @return array<string, mixed>
     */
    public function build(Acta $acta): array
    {
        $acta->loadMissing([
            'institution',
            'institucionDestino',
            'servicioDestino',
            'oficinaDestino',
            'equipos',
        ]);

        $settings = system_config();
        $headerLogoPath = $settings->logo_institucional_file_path ?? $settings->logo_pdf_file_path ?? null;

        $equipoQr = $this->resolveEquipoForQr($acta);
        $equipoPublicUrl = $equipoQr !== null
            ? route('equipos.public.show', ['uuid' => $equipoQr->uuid])
            : null;

        $destinoInstitucional = $this->buildDestinoInstitucional($acta);
        $prestamoDestinatario = $this->buildPrestamoDestinatario($acta);

        return [
            'pdfInstitutionName' => $acta->institution?->nombre ?: ($settings->nombre_sistema ?? config('app.name')),
            'pdfHeaderLogoPath' => $headerLogoPath,
            'pdfDocumentTitle' => $this->resolveTitle($acta),
            'equipoPublicUrl' => $equipoPublicUrl,
            'equipoQrSvg' => $this->generateQrSvg($equipoPublicUrl),
            'pdfDestinoInstitucional' => $destinoInstitucional,
            'pdfPrestamoDestinatario' => $prestamoDestinatario,
        ];
    }

    private function resolveEquipoForQr(Acta $acta): ?Equipo
    {
        return $acta->equipos
            ->first(fn (Equipo $equipo): bool => is_string($equipo->uuid) && $equipo->uuid !== '');
    }

    private function generateQrSvg(?string $url): ?string
    {
        if ($url === null || $url === '' || ! class_exists(QrCode::class)) {
            return null;
        }

        return QrCode::size(120)->generate($url);
    }

    private function resolveTitle(Acta $acta): string
    {
        return match ($acta->tipo) {
            Acta::TIPO_ENTREGA => 'ACTA DE ENTREGA DE EQUIPAMIENTO INFORMATICO',
            Acta::TIPO_PRESTAMO => 'ACTA DE PRESTAMO DE EQUIPAMIENTO INFORMATICO',
            Acta::TIPO_TRASLADO => 'ACTA DE TRASLADO DE EQUIPAMIENTO INFORMATICO',
            Acta::TIPO_BAJA => 'ACTA DE BAJA DE EQUIPAMIENTO INFORMATICO',
            Acta::TIPO_DEVOLUCION => 'ACTA DE DEVOLUCION DE EQUIPAMIENTO INFORMATICO',
            Acta::TIPO_MANTENIMIENTO => 'ACTA DE MANTENIMIENTO DE EQUIPAMIENTO INFORMATICO',
            default => 'ACTA DE EQUIPAMIENTO INFORMATICO',
        };
    }

    /**
     * @return array{institucion:?string,servicio:?string,oficina:?string,texto:string,has_data:bool}
     */
    private function buildDestinoInstitucional(Acta $acta): array
    {
        $institucion = $this->nullableTrim($acta->institucionDestino?->nombre);
        $servicio = $this->nullableTrim($acta->servicioDestino?->nombre);
        $oficina = $this->nullableTrim($acta->oficinaDestino?->nombre);

        $partes = array_values(array_filter([
            $institucion,
            $servicio,
            $oficina,
        ], fn (?string $value): bool => $value !== null && $value !== ''));

        $hasData = $partes !== [];

        return [
            'institucion' => $institucion,
            'servicio' => $servicio,
            'oficina' => $oficina,
            'texto' => $hasData ? implode(' / ', $partes) : '-',
            'has_data' => $hasData,
        ];
    }

    /**
     * @return array{is_prestamo:bool,nombre:?string,dni:?string,cargo:?string,dependencia:?string,has_data:bool,summary:string}
     */
    private function buildPrestamoDestinatario(Acta $acta): array
    {
        $nombre = $this->nullableTrim($acta->receptor_nombre);
        $dni = $this->nullableTrim($acta->receptor_dni);
        $cargo = $this->nullableTrim($acta->receptor_cargo);
        $dependencia = $this->nullableTrim($acta->receptor_dependencia);

        $hasData = collect([$nombre, $dni, $cargo, $dependencia])
            ->contains(fn (?string $value): bool => $value !== null && $value !== '');

        $summaryParts = array_values(array_filter([
            $nombre,
            $dni !== null && $dni !== '' ? 'DNI '.$dni : null,
            $cargo,
            $dependencia,
        ], fn (?string $value): bool => $value !== null && $value !== ''));

        return [
            'is_prestamo' => $acta->tipo === Acta::TIPO_PRESTAMO,
            'nombre' => $nombre,
            'dni' => $dni,
            'cargo' => $cargo,
            'dependencia' => $dependencia,
            'has_data' => $hasData,
            'summary' => $summaryParts !== [] ? implode(' | ', $summaryParts) : '',
        ];
    }

    private function nullableTrim(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
