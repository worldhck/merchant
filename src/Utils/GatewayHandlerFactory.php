<?php

namespace Arbory\Merchant\Utils;

use Arbory\Merchant\Utils\Handlers\DnbLinkHandler;
use Arbory\Merchant\Utils\Handlers\NordeaLinkHandler;
use Arbory\Merchant\Utils\Handlers\SebLinkHandler;
use Arbory\Merchant\Utils\Handlers\SwedbankBanklinkHandler;
use Arbory\Merchant\Utils\Handlers\WorldlineHandler;
use InvalidArgumentException;
use Omnipay\Common\GatewayInterface;

class GatewayHandlerFactory
{
    private $classMap = [
        'Omnipay\Worldline\Gateway' => WorldlineHandler::class,
        'Omnipay\SwedbankBanklink\Gateway' => SwedbankBanklinkHandler::class,
        'Omnipay\NordeaLink\Gateway' => NordeaLinkHandler::class,
        'Omnipay\DnbLink\Gateway' => DnbLinkHandler::class,
        'Omnipay\SebLink\Gateway' => SebLinkHandler::class
    ];

    public function create(GatewayInterface $gatewayInterface): GatewayHandler
    {
        $gatewayClassName = get_class($gatewayInterface);

        if (isset($this->classMap[$gatewayClassName])) {
            $formatterClass = $this->classMap[$gatewayClassName];

            return new $formatterClass();
        }

        throw new InvalidArgumentException('Unknown gateway type given');
    }

    public function addHandler($gatewayName, $formatterClass)
    {
        $this->classMap[$gatewayName] = $formatterClass;
    }
}
