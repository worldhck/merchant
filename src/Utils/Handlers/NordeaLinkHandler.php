<?php

namespace Arbory\Merchant\Utils\Handlers;

use Arbory\Merchant\Models\Transaction;
use Arbory\Merchant\Utils\GatewayHandler;
use Illuminate\Http\Request;

class NordeaLinkHandler extends GatewayHandler
{
    public function getTransactionReference(Request $request): string
    {
        return $request->get('SOLOPMT_RETURN_REF', '');
    }

    public function getPurchaseArguments(Transaction $transaction): array
    {
        return [
            'transactionReference' => $transaction->token_id,
            'transactionId' => $transaction->id,
        ];
    }

    public function getLanguage(string $suggestedLanguage): string
    {
        $defaultLangauge = 3;
        $codeToSupportedLang = [
            'en' => 3,
            'ee' => 4,
            'lv' => 6,
            'lt' => 7
        ];
        if (isset($codeToSupportedLang[$suggestedLanguage])) {
            return $codeToSupportedLang[$suggestedLanguage];
        }

        return $defaultLangauge;
    }
}
