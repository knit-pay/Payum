<?php

namespace Payum\Sofort\Action;

use ArrayAccess;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Sync;
use Payum\Sofort\Request\Api\GetTransactionData;

class SyncAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    public function execute(mixed $request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());

        if ($details['transaction_id']) {
            $this->gateway->execute(new GetTransactionData($details));
        }
    }

    public function supports(mixed $request): bool
    {
        return $request instanceof Sync &&
            $request->getModel() instanceof ArrayAccess
        ;
    }
}
