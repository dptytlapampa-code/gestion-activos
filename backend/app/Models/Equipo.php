<?php

namespace App\Models;

use App\Support\Auditing\Auditable;
use App\Services\EquipoStatusResolver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Equipo extends Model
{
    use Auditable, HasFactory;

    public const ESTADO_OPERATIVO = 'operativo';
    public const ESTADO_PRESTADO = 'prestado';
    public const ESTADO_EN_MANTENIMIENTO = 'mantenimiento';
    public const ESTADO_MANTENIMIENTO = self::ESTADO_EN_MANTENIMIENTO;
    public const ESTADO_FUERA_DE_SERVICIO = 'fuera_de_servicio';
    public const ESTADO_BAJA = 'baja';

    public const ESTADOS = [
        self::ESTADO_OPERATIVO,
        self::ESTADO_PRESTADO,
        self::ESTADO_EN_MANTENIMIENTO,
        self::ESTADO_FUERA_DE_SERVICIO,
        self::ESTADO_BAJA,
    ];

    protected $fillable = [
        'uuid',
        'tipo',
        'tipo_equipo_id',
        'marca',
        'modelo',
        'numero_serie',
        'bien_patrimonial',
        'mac_address',
        'codigo_interno',
        'estado',
        'equipo_status_id',
        'fecha_ingreso',
        'oficina_id',
    ];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected static function booted(): void
    {
        static::creating(function (Equipo $equipo): void {
            if (Schema::hasColumn($equipo->getTable(), 'uuid') && empty($equipo->uuid)) {
                $equipo->uuid = (string) Str::uuid();
            }

            if (! Schema::hasColumn($equipo->getTable(), 'equipo_status_id')) {
                return;
            }

            if ($equipo->equipo_status_id === null) {
                $equipo->equipo_status_id = app(EquipoStatusResolver::class)->resolveOrCreateOperativaId();
            }
        });
    }

    protected function casts(): array
    {
        return ['fecha_ingreso' => 'date'];
    }

    public function oficina(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'oficina_id');
    }

    public function tipoEquipo(): BelongsTo
    {
        return $this->belongsTo(TipoEquipo::class);
    }

    public function equipoStatus(): BelongsTo
    {
        return $this->belongsTo(EquipoStatus::class, 'equipo_status_id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class)->orderByDesc('fecha');
    }

    public function mantenimientos(): HasMany
    {
        return $this->hasMany(Mantenimiento::class)->orderByDesc('fecha')->orderByDesc('id');
    }

    public function actas(): BelongsToMany
    {
        return $this->belongsToMany(Acta::class, 'acta_equipo')->withPivot(['cantidad', 'accesorios', 'institucion_origen_id', 'institucion_origen_nombre', 'servicio_origen_id', 'servicio_origen_nombre', 'oficina_origen_id', 'oficina_origen_nombre']);
    }

    public function historial(): HasMany
    {
        return $this->hasMany(EquipoHistorial::class)->latest('fecha');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable')->latest();
    }

    public function tienePrestamoActivo(): bool
    {
        return Movimiento::query()
            ->where('equipo_id', $this->id)
            ->where('tipo_movimiento', Movimiento::TIPO_PRESTAMO)
            ->whereNull('fecha_devolucion_real')
            ->exists();
    }

    /**
     * @return array{institucion_id:int|null,servicio_id:int|null,oficina_id:int|null}
     */
    public function ubicacionActual(): array
    {
        $this->loadMissing('oficina.service.institution');

        return [
            'institucion_id' => $this->oficina?->service?->institution?->id,
            'servicio_id' => $this->oficina?->service?->id,
            'oficina_id' => $this->oficina?->id,
        ];
    }

    public function isOperativa(): bool
    {
        return $this->equipoStatus?->code === EquipoStatus::CODE_OPERATIVA;
    }

    public function isEnServicioTecnico(): bool
    {
        return $this->equipoStatus?->code === EquipoStatus::CODE_EN_SERVICIO_TECNICO;
    }

    public function isBaja(): bool
    {
        return $this->equipoStatus?->code === EquipoStatus::CODE_BAJA;
    }
}
