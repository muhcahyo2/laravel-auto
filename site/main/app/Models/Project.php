<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    const MODE_LARAVEL = 0;
    const MODE_EXPRESS_AND_FRONTEND = 1;

    const MODES = [
        self::MODE_LARAVEL => 'Laravel',
        self::MODE_EXPRESS_AND_FRONTEND => 'Express & Frontend'
    ];

    use HasFactory;

    protected $table = "projects";
    protected $primaryKey = 'id';

    protected $guarded = [
        'port',
        'dbuser',
        'dbname',
        'dbpassword'
    ];

    public function commands(): HasMany
    {
        return $this->hasMany(ProjectCommands::class);
    }
}
