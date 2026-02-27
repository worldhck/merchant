<?php

namespace Arbory\Merchant\Utils\Handlers;

use Arbory\Merchant\Models\Transaction;
use Arbory\Merchant\Utils\GatewayHandler;
use Illuminate\Http\Request;
use Omnipay\SwedbankBanklink\Utils\ProviderResolver;

class SwedbankBanklinkHandler extends GatewayHandler
{
    /**
     * Extract the payment ID from the callback request
     * V3 API: We use our own reference (token_id) in the return URL
     * since Swedbank preserves query parameters during redirect
     */
    public function getTransactionReference(Request $request): string
    {
        // First try to get our own reference from the return URL
        $ref = $request->get('ref', '');
        
        if ($ref) {
            // Find transaction by our token_id
            $transaction = \Arbory\Merchant\Models\Transaction::where('token_id', $ref)->first();
            if ($transaction && $transaction->token_reference) {
                // Return the Swedbank payment ID for status polling
                return $transaction->token_reference;
            }
        }
        
        // Fallback: try to get paymentId directly (if Swedbank sends it)
        return $request->get('paymentId', '');
    }

    /**
     * Prepare purchase arguments for the V3 gateway
     * Returns the parameters needed for initiating a payment
     */
    public function getPurchaseArguments(Transaction $transaction): array
    {
        $returnUrl = env('SWEDBANK_RETURN_URL');
        if (!$returnUrl) {
            $returnUrl = route('payments.completePurchase', ['gateway' => 'swedbank-banklink']);
        }
        
        // Append transaction token_id to return URL so we can identify the transaction on return
        // Swedbank will preserve query parameters in the redirect
        $returnUrl .= (strpos($returnUrl, '?') === false ? '?' : '&') . 'ref=' . $transaction->token_id;

        // Set notification URL
        $notificationUrl = env('SWEDBANK_NOTIFICATION_URL');
        if (!$notificationUrl) {
            $notificationUrl = route('payments.notification', ['gateway' => 'swedbank-banklink']);
        }

        return [
            'amount' => $transaction->order->total,
            'currency' => $transaction->order->currency ?? 'EUR',
            'provider' => $this->getProvider($transaction),
            'transactionId' => $transaction->id,
            'returnUrl' => $returnUrl,
            'notificationUrl' => $notificationUrl,
            'baseUrl' => env('SWEDBANK_GATEWAY_URL'),
        ];
    }

    /**
     * Prepare complete purchase arguments for status polling
     * Returns the parameters needed for checking payment status
     */
    public function getCompletePurchaseArguments(Transaction $transaction, Request $request): array
    {
        return [
            'transactionReference' => $transaction->token_reference,
        ];
    }

    /**
     * Extract provider BIC code from the order's payment_type via ProviderResolver.
     * Falls back to Swedbank's default BIC if no payment_type is available.
     */
    private function getProvider(Transaction $transaction): string
    {
        $order = $transaction->order;
        return ProviderResolver::resolve($order->payment_type ?? null);
    }

    /**
     * Map language codes to V3 API supported languages
     * V3 supports: LV, EN, RU, ET, LT
     */
    public function getLanguage(string $suggestedLanguage): string
    {
        $defaultLanguage = 'LV';
        $codeToSupportedLang = [
            'lv' => 'LV',
            'en' => 'EN',
            'ru' => 'RU',
            'et' => 'ET',
            'lt' => 'LT',
        ];

        if (isset($codeToSupportedLang[$suggestedLanguage])) {
            return $codeToSupportedLang[$suggestedLanguage];
        }

        return $defaultLanguage;
    }
}
