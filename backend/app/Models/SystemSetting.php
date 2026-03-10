<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = [
        'site_name',
        'primary_color',
        'sidebar_color',
        'logo_path',
    ];
}
