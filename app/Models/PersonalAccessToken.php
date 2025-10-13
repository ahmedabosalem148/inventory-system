<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonalAccessToken extends Model
{
    protected $fillable = [
        'tokenable_id',
        'tokenable_type',
        'name',
        'token',
        'abilities',
        'last_used_at',
        'expires_at',
    ];

    protected $casts = [
        'abilities' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the tokenable model
     */
    public function tokenable()
    {
        return $this->morphTo('tokenable');
    }

    /**
     * Delete token (revoke)
     */
    public function delete()
    {
        return parent::delete();
    }
}
