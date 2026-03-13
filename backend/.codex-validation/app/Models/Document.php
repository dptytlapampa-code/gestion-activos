<?php

namespace App\Models;

use App\Support\Auditing\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Document extends Model
{
    use Auditable;

    public const TYPES = [
        'factura',
        'remito',
        'acta',
        'presupuesto',
        'nota',
        'otro',
    ];

    protected $fillable = [
        'uploaded_by',
        'type',
        'note',
        'file_path',
        'original_name',
        'mime',
        'size',
    ];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
