<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sequence extends Model
{
    protected $fillable = [
        'entity_type',
        'year',
        'last_number',
    ];

    protected $casts = [
        'year' => 'integer',
        'last_number' => 'integer',
    ];

    /**
     * Supported entity types
     */
    public const ENTITY_TYPES = [
        'issue_vouchers',
        'return_vouchers',
        'transfer_vouchers',
        'payments',
        'customers',
        'cheques',
    ];
}
