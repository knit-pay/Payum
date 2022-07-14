<?php

namespace Payum\Klarna\Invoice\Action\Api;

use ArrayAccess;
use KlarnaException;
use KlarnaFlags;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Klarna\Invoice\Request\Api\ReturnAmount;

class ReturnAmountAction extends BaseApiAwareAction
{
    public function execute(mixed $request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());

        $klarna = $this->getKlarna();

        try {
            $klarna->returnAmount(
                $details['invoice_number'],
                $details['amount'],
                $details['vat'],
                $details['flags'] ?: KlarnaFlags::NO_FLAG,
                $details['description']
            );
        } catch (KlarnaException $e) {
            $this->populateDetailsWithError($details, $e, $request);
        }
    }

    public function supports(mixed $request): bool
    {
        return $request instanceof ReturnAmount &&
            $request->getModel() instanceof ArrayAccess
        ;
    }
}
