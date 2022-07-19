<?php

namespace Payum\Paypal\ProCheckout\Nvp\Tests\Action;

use Payum\Core\Request\GetHumanStatus;
use Payum\Core\Tests\GenericActionTest;
use Payum\Paypal\ProCheckout\Nvp\Action\StatusAction;
use Payum\Paypal\ProCheckout\Nvp\Api;

class StatusActionTest extends GenericActionTest
{
    /**
     * @var class-string<StatusAction>
     */
    protected $actionClass = StatusAction::class;

    /**
     * @var class-string<GetHumanStatus>
     */
    protected $requestClass = GetHumanStatus::class;

    public function testShouldMarkNewIfDetailsEmpty(): void
    {
        $action = new StatusAction();

        $status = new GetHumanStatus([]);

        //guard
        $status->markUnknown();

        $action->execute($status);

        $this->assertTrue($status->isNew());
    }

    public function testShouldMarkFailedIfResultNotSupported(): void
    {
        $action = new StatusAction();

        $status = new GetHumanStatus([
            'RESULT' => 123,
        ]);

        //guard
        $status->markNew();

        $action->execute($status);

        $this->assertTrue($status->isFailed());
    }

    public function testShouldMarkCapturedIfResultSuccess(): void
    {
        $action = new StatusAction();

        $status = new GetHumanStatus([
            'TRXTYPE' => Api::TRXTYPE_SALE,
            'RESULT' => Api::RESULT_SUCCESS,
        ]);

        //guard
        $status->markNew();

        $action->execute($status);

        $this->assertTrue($status->isCaptured());
    }

    public function testShouldMarkRefundedIfOrigIdSetAndTrxTypeCreditAndResultSuccess(): void
    {
        $action = new StatusAction();

        $status = new GetHumanStatus([
            'TRXTYPE' => Api::TRXTYPE_CREDIT,
            'RESULT' => Api::RESULT_SUCCESS,
            'ORIGID' => 'anId',
        ]);

        //guard
        $status->markNew();

        $action->execute($status);

        $this->assertTrue($status->isRefunded());
    }

    public function testShouldMarkFailedIfResultGreaterThenZero(): void
    {
        $action = new StatusAction();

        $status = new GetHumanStatus([
            'RESULT' => 1,
        ]);

        //guard
        $status->markNew();

        $action->execute($status);

        $this->assertTrue($status->isFailed());

        $status = new GetHumanStatus([
            'RESULT' => 100000,
        ]);

        //guard
        $status->markNew();

        $action->execute($status);

        $this->assertTrue($status->isFailed());
    }

    public function testShouldMarkUnknownIfResultSuccessButTrxTypeNotPurchaseOne(): void
    {
        $action = new StatusAction();

        $status = new GetHumanStatus([
            'TRXTYPE' => Api::TRXTYPE_CREDIT,
            'RESULT' => Api::RESULT_SUCCESS,
        ]);

        //guard
        $status->markNew();

        $action->execute($status);

        $this->assertTrue($status->isUnknown());
    }
}
