<?php

namespace App\Models;

use App\Support\Auditing\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use Auditable, HasFactory;

    protected $fillable = ['institution_id', 'nombre', 'descripcion'];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function offices(): HasMany
    {
        return $this->hasMany(Office::class);
    }
}
