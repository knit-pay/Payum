<?php

namespace Payum\Stripe\Extension;

use ArrayAccess;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Extension\Context;
use Payum\Core\Extension\ExtensionInterface;
use Payum\Core\GatewayInterface;
use Payum\Core\Request\Capture;
use Payum\Stripe\Constants;
use Payum\Stripe\Request\Api\CreateCustomer;
use Payum\Stripe\Request\Api\ObtainToken;

class CreateCustomerExtension implements ExtensionInterface
{
    /**
     * @var Context
     */
    public function onPreExecute(Context $context)
    {
        /** @var Capture $request */
        $request = $context->getRequest();
        if (false == $request instanceof Capture) {
            return;
        }

        $model = $request->getModel();
        if (false == $model instanceof ArrayAccess) {
            return;
        }

        $this->createCustomer($context->getGateway(), ArrayObject::ensureArrayObject($model));
    }

    /**
     * @var Context
     */
    public function onExecute(Context $context)
    {
    }

    /**
     * @var Context
     */
    public function onPostExecute(Context $context)
    {
        $model = null;
        /** @var Capture $request */
        $request = $context->getRequest();
        if (false == $request instanceof ObtainToken) {
            return;
        }

        $model = $request->getModel();
        if (false == $model instanceof ArrayAccess) {
            return;
        }

        $this->createCustomer($context->getGateway(), ArrayObject::ensureArrayObject($model));
    }

    protected function createCustomer(GatewayInterface $gateway, ArrayObject $model)
    {
        if ($model['customer']) {
            return;
        }
        if (false == ($model['card'] && is_string($model['card']))) {
            return;
        }

        $local = $model->getArray('local');
        if (false == $local['save_card']) {
            return;
        }

        $customer = $local->getArray('customer');
        $customer['card'] = $model['card'];

        $gateway->execute(new CreateCustomer($customer));

        $local['customer'] = $customer->toUnsafeArray();
        $model['local'] = $local->toUnsafeArray();
        unset($model['card']);

        if ($customer['id']) {
            $model['customer'] = $customer['id'];
        } else {
            $model['status'] = Constants::STATUS_FAILED;
        }
    }
}
