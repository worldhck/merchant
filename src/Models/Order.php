<?php

namespace Arbory\Merchant\Models;

use App\OrderHelper;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Order
 *
 * @mixin \Eloquent
 */
class Order extends Model
{
    protected $table = 'merchant_orders';

    protected $fillable = [
        'status',
        'total',
        'payment_currency',
        'status_updated_at',
        'payment_started_at',
        'language',
        'session_id',
        'owner_type',
        'owner_id',
        'client_ip'
    ];

    public function owner()
    {
        return $this->morphTo('owner');
    }

    public function orderLines()
    {
        return $this->hasMany(OrderLine::class, 'order_id', 'id');
    }

    public function getCurrency()
    {
        return $this->currency_code;
    }

    public function __toString()
    {
        return (string) OrderHelper::getOrderNumber($this);
    }

    public function transactions()
    {
        return $this->morphMany(Transaction::class, 'object', 'object_id', 'object_class');
    }
}
