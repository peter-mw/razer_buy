<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Catalog extends Model
{
    /**
     * Get the games that belong to this catalog's region.
     */
    public function games()
    {
        return $this->hasMany(Game::class, 'region_id', 'region_id');
    }

    protected $fillable = [
        'id',
        'title',
        'permalink',
        'region_id'
    ];

    // Disable auto-incrementing as we're using a string ID
    public $incrementing = false;
    
    // Specify the ID is a string
    protected $keyType = 'string';
}
