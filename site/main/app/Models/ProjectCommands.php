<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectCommands extends Model
{
    use HasFactory;

    const TEMPLATE_COMMAND_LARAVEL = [
        "composer install",
        "php artisan migrate",
        "php artisan optimize"
    ];

    const TEMPLATE_COMMANDS = [
        Project::MODE_LARAVEL => self::TEMPLATE_COMMAND_LARAVEL,
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
