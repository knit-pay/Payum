<?php

namespace Payum\Paypal\Masspay\Nvp\Tests\Action;

use Iterator;
use Payum\Core\GatewayInterface;
use Payum\Core\Model\Payout;
use Payum\Core\Model\PayoutInterface;
use Payum\Core\Request\Convert;
use Payum\Core\Request\Generic;
use Payum\Core\Request\GetCurrency;
use Payum\Core\Security\TokenInterface;
use Payum\Core\Tests\GenericActionTest;
use Payum\Paypal\Masspay\Nvp\Action\ConvertPayoutAction;
use stdClass;

class ConvertPayoutActionTest extends GenericActionTest
{
    protected $actionClass = ConvertPayoutAction::class;

    protected $requestClass = Convert::class;

    public function provideSupportedRequests(): Iterator
    {
        yield [new $this->requestClass(new Payout(), 'array')];
        yield [new $this->requestClass($this->createMock(PayoutInterface::class), 'array')];
        yield [new $this->requestClass(new Payout(), 'array', $this->createMock(TokenInterface::class))];
    }

    public function provideNotSupportedRequests(): Iterator
    {
        yield ['foo'];
        yield [['foo']];
        yield [new stdClass()];
        yield [$this->getMockForAbstractClass(Generic::class, [[]])];
        yield [new $this->requestClass(new stdClass(), 'array')];
        yield [new $this->requestClass(new Payout(), 'foobar')];
        yield [new $this->requestClass($this->createMock(PayoutInterface::class), 'foobar')];
    }

    public function testShouldCorrectlyConvertPayoutToDetails()
    {
        $gatewayMock = $this->createMock(GatewayInterface::class);
        $gatewayMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->isInstanceOf(GetCurrency::class))
            ->willReturnCallback(function (GetCurrency $request) {
                $request->name = 'US Dollar';
                $request->alpha3 = 'USD';
                $request->numeric = 123;
                $request->exp = 2;
                $request->country = 'US';
            })
        ;

        $payoutModel = new Payout();
        $payoutModel->setRecipientId('theRecipientId');
        $payoutModel->setCurrencyCode('USD');
        $payoutModel->setTotalAmount(123);
        $payoutModel->setDescription('the description');

        $action = new ConvertPayoutAction();
        $action->setGateway($gatewayMock);

        $action->execute($convert = new Convert($payoutModel, 'array'));

        $details = $convert->getResult();

        $this->assertNotEmpty($details);

        $this->assertEquals([
            'CURRENCYCODE' => 'USD',
            'L_AMT0' => 1.23,
            'L_NOTE0' => 'the description',
            'RECEIVERTYPE' => 'UserID',
            'L_RECEIVERID0' => 'theRecipientId',
        ], $details);
    }

    public function testShouldNotOverwriteAlreadySetExtraDetails()
    {
        $gatewayMock = $this->createMock(GatewayInterface::class);
        $gatewayMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->isInstanceOf(GetCurrency::class))
            ->willReturnCallback(function (GetCurrency $request) {
                $request->name = 'US Dollar';
                $request->alpha3 = 'USD';
                $request->numeric = 123;
                $request->exp = 2;
                $request->country = 'US';
            })
        ;

        $payoutModel = new Payout();
        $payoutModel->setRecipientEmail('theRecipientEmail');
        $payoutModel->setCurrencyCode('USD');
        $payoutModel->setTotalAmount(123);
        $payoutModel->setDescription('the description');
        $payoutModel->setDetails([
            'foo' => 'fooVal',
        ]);

        $action = new ConvertPayoutAction();
        $action->setGateway($gatewayMock);

        $action->execute($convert = new Convert($payoutModel, 'array'));

        $details = $convert->getResult();

        $this->assertNotEmpty($details);

        $this->assertEquals([
            'CURRENCYCODE' => 'USD',
            'L_AMT0' => 1.23,
            'L_NOTE0' => 'the description',
            'RECEIVERTYPE' => 'EmailAddress',
            'L_EMAIL0' => 'theRecipientEmail',
            'foo' => 'fooVal',
        ], $details);
    }
}
