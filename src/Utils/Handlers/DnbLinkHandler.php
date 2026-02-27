<?php

namespace Arbory\Merchant\Utils\Handlers;

use Arbory\Merchant\Models\Transaction;
use Arbory\Merchant\Utils\GatewayHandler;
use Illuminate\Http\Request;

class DnbLinkHandler extends GatewayHandler
{
    public function getTransactionReference(Request $request): string
    {
        return $request->get('VK_REF', '');
    }

    public function getPurchaseArguments(Transaction $transaction): array
    {
        return [
            'transactionReference' => $transaction->token_id
        ];
    }

    public function getLanguage(string $suggestedLanguage): string
    {
        $defaultLangauge = 'LAT';
        $codeToSupportedLang = [
            'lv' => 'LAT',
            'ru' => 'RUS',
            'en' => 'ENG'
        ];
        if (isset($codeToSupportedLang[$suggestedLanguage])) {
            return $codeToSupportedLang[$suggestedLanguage];
        }

        return $defaultLangauge;
    }
}
