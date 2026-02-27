<?php

namespace Arbory\Merchant\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin \Eloquent
 */
class Transaction extends Model
{
    public const STATUS_CREATED = 1;

    public const STATUS_INITIALIZED = 2;

    public const STATUS_ACCEPTED = 3;

    public const STATUS_PROCESSED = 4;

    public const STATUS_ERROR = 5;

    public const STATUS_REVERSED = 6;

    protected $table = 'merchant_transactions';

    protected $fillable = ['object_class', 'object_id', 'status', 'gateway', 'options', 'amount', 'token_id', 'description', 'language_code', 'currency_code', 'client_ip'];

    protected $casts = [
        'options' => 'array'
    ];

    public function order()
    {
        return $this->morphTo('order', 'object_class', 'object_id');
    }
}
