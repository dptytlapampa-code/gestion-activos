<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class EquipoStatus extends Model
{
    public const CODE_OPERATIVA = 'OPERATIVA';
    public const CODE_PRESTADO = 'PRESTADO';
    public const CODE_PRESTADA = 'PRESTADA';
    public const CODE_EN_SERVICIO_TECNICO = 'EN_SERVICIO_TECNICO';
    public const CODE_FUERA_DE_SERVICIO = 'FUERA_DE_SERVICIO';
    public const CODE_BAJA = 'BAJA';

    protected $fillable = ['code', 'name', 'color', 'is_terminal'];

    protected function casts(): array
    {
        return ['is_terminal' => 'boolean'];
    }

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class);
    }

    /**
     * @return array<int, string>
     */
    public static function canonicalCodes(): array
    {
        return [
            self::CODE_OPERATIVA,
            self::CODE_PRESTADO,
            self::CODE_EN_SERVICIO_TECNICO,
            self::CODE_FUERA_DE_SERVICIO,
            self::CODE_BAJA,
        ];
    }

    /**
     * @return array<string, array{code:string,name:string,color:string,is_terminal:bool}>
     */
    public static function canonicalDefinitions(): array
    {
        return [
            self::CODE_OPERATIVA => [
                'code' => self::CODE_OPERATIVA,
                'name' => 'Operativa',
                'color' => 'green',
                'is_terminal' => false,
            ],
            self::CODE_PRESTADO => [
                'code' => self::CODE_PRESTADO,
                'name' => 'Prestado',
                'color' => 'blue',
                'is_terminal' => false,
            ],
            self::CODE_EN_SERVICIO_TECNICO => [
                'code' => self::CODE_EN_SERVICIO_TECNICO,
                'name' => 'En Servicio Tecnico',
                'color' => 'yellow',
                'is_terminal' => false,
            ],
            self::CODE_FUERA_DE_SERVICIO => [
                'code' => self::CODE_FUERA_DE_SERVICIO,
                'name' => 'Fuera de Servicio',
                'color' => 'orange',
                'is_terminal' => false,
            ],
            self::CODE_BAJA => [
                'code' => self::CODE_BAJA,
                'name' => 'Baja',
                'color' => 'red',
                'is_terminal' => true,
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function aliasesForCanonicalCode(string $canonicalCode): array
    {
        $canonicalCode = self::normalizeCode($canonicalCode);

        return match ($canonicalCode) {
            self::CODE_OPERATIVA => [self::CODE_OPERATIVA, 'OPERATIVO'],
            self::CODE_PRESTADO => [self::CODE_PRESTADO, self::CODE_PRESTADA],
            self::CODE_EN_SERVICIO_TECNICO => [self::CODE_EN_SERVICIO_TECNICO],
            self::CODE_FUERA_DE_SERVICIO => [self::CODE_FUERA_DE_SERVICIO],
            self::CODE_BAJA => [self::CODE_BAJA],
            default => [],
        };
    }

    public static function normalizeCode(string $code): string
    {
        return Str::upper(trim($code));
    }
}
