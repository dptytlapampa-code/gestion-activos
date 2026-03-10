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
        $acta->loadMissing(['institution', 'equipos']);

        $settings = system_config();
        $headerLogoPath = $settings->logo_institucional_file_path ?? $settings->logo_pdf_file_path ?? null;

        $equipoQr = $this->resolveEquipoForQr($acta);
        $equipoPublicUrl = $equipoQr !== null
            ? route('equipos.public.show', ['uuid' => $equipoQr->uuid])
            : null;

        return [
            'pdfInstitutionName' => $acta->institution?->nombre ?: ($settings->nombre_sistema ?? config('app.name')),
            'pdfHeaderLogoPath' => $headerLogoPath,
            'pdfDocumentTitle' => $this->resolveTitle($acta),
            'equipoPublicUrl' => $equipoPublicUrl,
            'equipoQrSvg' => $this->generateQrSvg($equipoPublicUrl),
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
}
