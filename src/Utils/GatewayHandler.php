<?php

namespace Arbory\Merchant\Utils;

use Arbory\Merchant\Models\Transaction;
use Illuminate\Http\Request;

abstract class GatewayHandler
{
    abstract public function getTransactionReference(Request $request): string;

    public function getCompletePurchaseArguments(Transaction $transaction, Request $request): array
    {
        return [];
    }

    public function getPurchaseArguments(Transaction $transaction): array
    {
        return [];
    }

    public function getReversalArguments(Transaction $transaction): array
    {
        return [];
    }

    /**
     * @param  string  $suggestedLanguage  2 character code, used standard - https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes
     */
    public function getLanguage(string $suggestedLanguage): string
    {
        return $suggestedLanguage;
    }
}
