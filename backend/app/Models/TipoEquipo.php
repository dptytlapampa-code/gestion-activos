<?php

namespace App\Models;

use App\Services\PublicMediaService;
use App\Support\Auditing\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoEquipo extends Model
{
    use Auditable, HasFactory;

    protected $table = 'tipos_equipos';

    protected $fillable = ['nombre', 'descripcion', 'image_path'];

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class);
    }

    public function getImagePathAttribute(?string $value): ?string
    {
        return app(PublicMediaService::class)->normalizeStoredPath($value);
    }

    public function getImageUrlAttribute(): ?string
    {
        return app(PublicMediaService::class)->url($this->getRawOriginal('image_path'));
    }
}
