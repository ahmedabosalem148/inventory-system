<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sequence extends Model
{
    protected $fillable = [
        'entity_type',
        'year',
        'last_number',
        'prefix',
        'min_value',
        'max_value',
        'increment_by',
        'auto_reset',
    ];

    protected $casts = [
        'year' => 'integer',
        'last_number' => 'integer',
        'min_value' => 'integer',
        'max_value' => 'integer',
        'increment_by' => 'integer',
        'auto_reset' => 'boolean',
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
