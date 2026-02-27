<?php

namespace Arbory\Merchant\Utils;

use Arbory\Merchant\Models\Transaction;

class Response
{
    private $isSuccessful;

    private $transaction;

    public function __construct(bool $isSuccessful, ?Transaction $transaction = null)
    {
        $this->isSuccessful = $isSuccessful;
        $this->transaction = $transaction;
    }

    public function isSuccessful()
    {
        return $this->isSuccessful;
    }

    public function getTransaction()
    {
        return $this->transaction;
    }
}
