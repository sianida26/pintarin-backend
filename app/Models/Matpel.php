<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Matpel extends Model
{
    use HasFactory;

    /**
     * Get all of the kelases for the Matpel
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function kelases(): HasMany
    {
        return $this->hasMany(Kelas::class);
    }
}
