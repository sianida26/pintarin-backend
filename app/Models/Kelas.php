<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Crypt;

use Faker\Factory as Faker;

class Kelas extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [];

    /**
     * Get the guru that owns the Kelas
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    /**
     * Retrieve enroll token.
     *
     * @return string
     */
    public function getEnrollToken(): string
    {
        $faker = Faker::create();
        return Crypt::encryptString( $this->id . '-' . $faker->regexify('\w{1,10}'));
    }
}
