<?php

namespace App\Models;

use App\Support\Auditing\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Office extends Model
{
    use Auditable, HasFactory;

    protected $fillable = ['service_id', 'nombre', 'descripcion'];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class, 'oficina_id');
    }
}
