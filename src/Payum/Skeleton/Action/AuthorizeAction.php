<?php

namespace Payum\Skeleton\Action;

use ArrayAccess;
use LogicException;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Authorize;

class AuthorizeAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    public function execute(mixed $request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        throw new LogicException('Not implemented');
    }

    public function supports(mixed $request): bool
    {
        return $request instanceof Authorize &&
            $request->getModel() instanceof ArrayAccess
        ;
    }
}
