<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Office extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'name',
        'floor',
        'active',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
