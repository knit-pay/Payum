<?php

namespace Payum\Klarna\Invoice\Action\Api;

use ArrayAccess;
use KlarnaException;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Klarna\Invoice\Request\Api\CreditInvoice;

class CreditInvoiceAction extends BaseApiAwareAction
{
    public function execute(mixed $request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());

        $details->validateNotEmpty(['invoice_number']);

        $klarna = $this->getKlarna();

        try {
            $klarna->creditInvoice($details['invoice_number']);
        } catch (KlarnaException $e) {
            $this->populateDetailsWithError($details, $e, $request);
        }
    }

    public function supports(mixed $request): bool
    {
        return $request instanceof CreditInvoice &&
            $request->getModel() instanceof ArrayAccess
        ;
    }
}
