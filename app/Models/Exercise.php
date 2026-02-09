<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exercise extends Model
{
    /** @use HasFactory<\Database\Factories\ExerciseFactory> */
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'instruction',
    ];
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    
}
