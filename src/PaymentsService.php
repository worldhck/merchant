<?php

namespace Arbory\Merchant;

use Arbory\Merchant\Models\Order;
use Arbory\Merchant\Models\Transaction;
use Arbory\Merchant\Utils\GatewayHandlerFactory;
use Arbory\Merchant\Utils\Response;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Omnipay;
use Omnipay\Common\GatewayInterface;
use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\ResponseInterface;

class PaymentsService
{
    /**
     * @return Response redirects or returns Response with status and orders transaction
     */
    public function purchase(Order $order, string $gatewayName, array $customArgs)
    {
        try {
            // Will throw exception on nonexistent gateway
            $gatewayObj = Omnipay::gateway($gatewayName);

            /** @var Transaction $transaction */
            $transaction = $this->createTransaction($order, $gatewayObj);
            $this->setPurchaseArgs($transaction, $gatewayObj, $customArgs);
            $this->setTransactionInitialized($transaction);

            try {
                $purchaseRequest = $gatewayObj->purchase($transaction->options['purchase']);
                $this->logTransactionRequest($transaction, $purchaseRequest->getData());

                $response = $purchaseRequest->send();
                $this->logTransactionResponse($transaction, $response->getData());

                if ($response->isSuccessful()) {
                    // Save transactions reference, if gateway responds with it
                    $this->saveTransactionReference($transaction, $response);
                    $this->setTransactionProcessed($transaction);
                    return new Response(true, $transaction);

                } elseif ($response->isRedirect() || $response->isTransparentRedirect()) {
                    // Save transactions reference, if gateway responds with it
                    $this->saveTransactionReference($transaction, $response);
                    $this->setTransactionAccepted($transaction);
                    $this->logTransactionRequest($transaction, ['redirect to merchant..']);
                    $response->redirect();
                } else {
                    // Payment failed
                    $this->logTransactionError($transaction, $response->getMessage());
                }
            } catch (Exception $e) {
                // Validation or other errors
                $this->logTransactionError($transaction, $e->getMessage());
            }

            // Log response error
            $this->setTransactionError($transaction);

            return new Response(false, $transaction);

        } catch (\Exception $e) {
            //unknown gateway or transaction errors
            \Log::error($e->getMessage());
        }

        return new Response(false);
    }

    /**
     * Returns transaction if payment completed or false if payment failed
     *
     * @return Response
     */
    public function completePurchase(string $gatewayName, Request $request)
    {
        try {
            $gatewayObj = Omnipay::gateway($gatewayName);

            /** @var Transaction $transaction */
            $transaction = $this->getRequestsTransaction($gatewayObj, $request);
            $this->logTransactionResponse($transaction, $request->input());

            try {
                // Send complete request
                $this->setCompletionArgs($transaction, $gatewayObj, $request);
                $completeRequest = $gatewayObj->completePurchase($transaction->options['completePurchase']);
                $this->logTransactionRequest($transaction, $completeRequest->getData());

                // Receive completion response
                $response = $completeRequest->send();
                $this->logTransactionResponse($transaction, $response->getData());

                if ($response->isSuccessful()) {
                    $this->setTransactionProcessed($transaction);

                    return new Response(true, $transaction);
                }
                $this->logTransactionError($transaction, $response->getMessage());

            } catch (Exception $e) {
                // Validation or other errors
                $this->logTransactionError($transaction, $e->getMessage());
            }

            // Log response error
            $this->setTransactionError($transaction);

            return new Response(false, $transaction);

        } catch (Exception $e) {
            // Log error in file, we have no transaction to log this to
            \Log::warning('PaymentService:completePurchase:'.$e->getMessage());
        }

        return new Response(false);
    }

    /**
     * Reverse transaction (master card specific), for technical errors only otherwise use refund
     *
     * @return Response
     *
     * @throws Exception
     */
    public function reverseTransaction(string $gatewayName, Transaction $transaction, $amount)
    {
        $gatewayObj = Omnipay::gateway($gatewayName);

        // this is custom method gateways method
        // TODO: combine reverse with refund and determine correct response via passed option value?
        if (!method_exists($gatewayObj, 'reverse')) {
            throw new Exception("$transaction->gateway does not support transaction reversal");
        }

        $this->setReversalArgs($transaction, $gatewayObj, ['amount' => $amount]);
        /** @var AbstractResponse $request */
        $request = $gatewayObj->reverse($transaction->options['reverseTransaction']);
        $this->logTransactionRequest($transaction, $request->getData());
        /** @var AbstractResponse $response */
        $response = $request->send();
        $this->logTransactionResponse($transaction, $response->getData());

        if ($response->isSuccessful()) {
            // mark transaction as reversed
            $transaction->status = Transaction::STATUS_REVERSED;
            $transaction->save();

            return new Response(true, $transaction);
        }

        $this->logTransactionError($transaction, $response->getData());

        return new Response(false, $transaction);
    }

    public function closeDay(string $gatewayName)
    {
        $gatewayObj = Omnipay::gateway($gatewayName);
        // this is custom method gateways method
        if (!method_exists($gatewayObj, 'closeDay')) {
            throw new Exception("$gatewayName does not support business day closing");
        }
        /** @var AbstractRequest $request */
        $request = $gatewayObj->closeDay();
        /** @var AbstractResponse $response */
        $response = $request->send();
        \Log::info('PaymentService:closeDay - ' . print_r($response->getData(), 1));
        return new Response($response->isSuccessful());
    }

    /**
     * Some gateways will send their token reference (gateways own token for transaction)
     */
    private function saveTransactionReference(Transaction $transaction, ResponseInterface $response)
    {
        $transaction->token_reference = $response->getTransactionReference();
        $transaction->save();
    }

    private function setReversalArgs(Transaction $transaction, GatewayInterface $gatewayObj, $customArgs)
    {
        $gatewayHandler = (new GatewayHandlerFactory())->create($gatewayObj);

        $gatewayArgs = $gatewayHandler->getReversalArguments($transaction);
        $allArguments = $customArgs + $gatewayArgs;

        $options = $transaction->options;
        $options['reverseTransaction'] = $allArguments;
        $transaction->options = $options;
        $transaction->save();
    }

    private function getRequestsTransaction(GatewayInterface $gateway, Request $request): Transaction
    {
        $gatewayClassName = get_class($gateway);
        $gatewayHandler = (new GatewayHandlerFactory())->create($gateway);
        $transactionRef = $gatewayHandler->getTransactionReference($request);

        if ($transactionRef) {
            // Get by unique reference token per gateway
            return Transaction::where('token_reference', $transactionRef)->where('gateway', $gatewayClassName)->firstOrFail();
        }

        throw new InvalidArgumentException('Transaction not found');
    }

    private function setCompletionArgs(Transaction $transaction, GatewayInterface $gatewayObj, Request $request)
    {
        $gatewayHandler = (new GatewayHandlerFactory())->create($gatewayObj);
        $options = $transaction->options;
        $options['completePurchase'] = $gatewayHandler->getCompletePurchaseArguments($transaction, $request);
        $transaction->options = $options;
        $transaction->save();
    }

    private function setPurchaseArgs(Transaction $transaction, GatewayInterface $gatewayObj, $customArgs)
    {
        // Each gateway can have different request arguments
        $gatewayHandler = (new GatewayHandlerFactory())->create($gatewayObj);
        $gatewayArgs = $gatewayHandler->getPurchaseArguments($transaction);

        // These arguments are common for all gateways
        $commonArgs = [
            'language' => $transaction->language_code, // gateway dependant
            'amount' => $this->transformToFloat($transaction->amount),
            'currency' => $transaction->currency_code
        ];

        // Custom arguments from checkout (purchase description etc.)
        $args = $customArgs + $commonArgs + $gatewayArgs;

        $options = $transaction->options;
        $options['purchase'] = $args;
        $transaction->options = $options;
        $transaction->save();
    }

    // Int to float with 2 numbers after floating point
    private function transformToFloat($intValue)
    {
        // round and float
        $float = round(($intValue / 100), 2, PHP_ROUND_HALF_EVEN);

        return number_format($float, 2, '.', '');
    }

    private function createTransaction(Order $order, GatewayInterface $gateway)
    {
        return Transaction::create([
            'object_class' => get_class($order),
            'object_id' => $order->id,
            'status' => Transaction::STATUS_CREATED,
            'gateway' => get_class($gateway),
            'options' => [], // will be populated on every request
            'amount' => $order->total,
            'token_id' => Str::random('20'), // TODO: Do we need internal token?
            'description' => '',
            'language_code' => $this->getGatewaysLanguage($gateway, $order->language),
            'currency_code' => $order->payment_currency,
            'client_ip' => $order->client_ip
        ]);
    }

    /**
     * Gets gateways supported language code. $suggestedLanguage serves two purposes :
     * 1. if gateway only accepts specific language codes, default or the closest one to $suggestedLanguage will be returned
     * 2. if gateway has custom language codes, then $suggestedLanguage will be returned and used
     *
     * @param  string  $suggestedLanguage  //2 characters code for language https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes
     * @return string
     */
    protected function getGatewaysLanguage(GatewayInterface $gateway, string $suggestedLanguage)
    {
        return (new GatewayHandlerFactory())->create($gateway)->getLanguage($suggestedLanguage);
    }

    protected function setTransactionInitialized(Transaction $transaction)
    {
        $transaction->status = Transaction::STATUS_INITIALIZED;
        $transaction->save();
    }

    protected function setTransactionAccepted(Transaction $transaction)
    {
        $transaction->status = Transaction::STATUS_ACCEPTED;
        $transaction->save();
    }

    protected function setTransactionProcessed(Transaction $transaction)
    {
        $transaction->status = Transaction::STATUS_PROCESSED;
        $transaction->save();
    }

    protected function setTransactionError(Transaction $transaction)
    {
        $transaction->status = Transaction::STATUS_ERROR;
        $transaction->save();
    }

    protected function logTransactionError(Transaction $transaction, $msg)
    {
        $transaction->refresh();
        $transaction->error = $transaction->error . '[error|'. date('Y.m.d h:i:s') .']: ' . print_r($msg, true);
        $transaction->save();
    }

    protected function logTransactionRequest(Transaction $transaction, $msg)
    {
        $transaction->refresh();
        $transaction->response = $transaction->response . '[request|'. date('Y.m.d h:i:s').']: '  . print_r($msg, true);
        $transaction->save();
    }

    protected function logTransactionResponse(Transaction $transaction, $msg)
    {
        $transaction->refresh();
        $transaction->response = $transaction->response . '[response|'. date('Y.m.d h:i:s').']: '  . print_r($msg, true);
        $transaction->save();
    }
}
