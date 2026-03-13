<?php

namespace App\Models;

use App\Support\Auditing\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class TipoEquipo extends Model
{
    use Auditable, HasFactory;

    protected $table = 'tipos_equipos';

    protected $fillable = ['nombre', 'descripcion', 'image_path'];

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        if ($this->image_path === null || $this->image_path === '') {
            return null;
        }

        if (! Storage::disk('public')->exists($this->image_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->image_path);
    }
}