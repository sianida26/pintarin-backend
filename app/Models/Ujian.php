<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ujian extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'isUjian' => 'boolean',
    ];

    /**
     * Get the guru that owns the Ujian
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    /**
     * The kelas that belong to the Ujian
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function kelas(): BelongsToMany
    {
        return $this->belongsToMany(Kelas::class);
    }

    /**
     * Get all of the soals for the Ujian
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function soals(): HasMany
    {
        return $this->hasMany(Soal::class);
    }

    /**
     * Get all of the ujianResult for the Ujian
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ujianResults(): HasMany
    {
        return $this->hasMany(UjianResult::class);
    }
}
