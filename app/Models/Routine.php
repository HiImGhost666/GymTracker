<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Routine extends Model
{
    /** @use HasFactory<\Database\Factories\RoutineFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    public function exercises()
    {
        return $this->belongsToMany(Exercise::class)
                    ->withPivot('sequence', 'target_sets', 'target_reps', 'rest_seconds')
                    ->withTimestamps();
    }
    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    
}
