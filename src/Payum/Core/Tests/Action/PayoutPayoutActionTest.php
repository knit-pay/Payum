<?php

namespace Payum\Core\Tests\Action;

use ArrayAccess;
use Exception;
use Iterator;
use function iterator_to_array;
use Payum\Core\Action\PayoutPayoutAction;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\Model\Payout as PayoutModel;
use Payum\Core\Model\PayoutInterface;
use Payum\Core\Request\Convert;
use Payum\Core\Request\Generic;
use Payum\Core\Request\GetHumanStatus;
use Payum\Core\Request\Payout;
use Payum\Core\Security\TokenInterface;
use Payum\Core\Tests\GenericActionTest;
use ReflectionClass;

class PayoutPayoutActionTest extends GenericActionTest
{
    /**
     * @var class-string<\Payum\Core\Request\Payout>
     */
    protected $requestClass = Payout::class;

    /**
     * @var class-string<PayoutPayoutAction>
     */
    protected $actionClass = PayoutPayoutAction::class;

    /**
     * @return \Iterator<Generic[]>
     */
    public function provideSupportedRequests(): Iterator
    {
        $payout = new $this->requestClass($this->createMock(TokenInterface::class));
        $payout->setModel($this->createMock(PayoutInterface::class));
        yield [new $this->requestClass(new PayoutModel())];
        yield [$payout];
    }

    public function testShouldImplementGatewayAwareInterface(): void
    {
        $rc = new ReflectionClass($this->actionClass);

        $this->assertTrue($rc->implementsInterface(GatewayAwareInterface::class));
    }

    public function testShouldExecuteConvertRequestIfStatusNew(): void
    {
        $payoutModel = new PayoutModel();

        $testCase = $this;

        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->atLeast(2))
            ->method('execute')
            ->withConsecutive([$this->isInstanceOf(GetHumanStatus::class)], [$this->isInstanceOf(Convert::class)])
            ->willReturnOnConsecutiveCalls(
                $this->returnCallback(function (GetHumanStatus $request): void {
                    $request->markNew();
                }),
                $this->returnCallback(function (Convert $request) use ($testCase, $payoutModel): void {
                    $testCase->assertSame($payoutModel, $request->getSource());
                    $testCase->assertSame('array', $request->getTo());
                    $testCase->assertNull($request->getToken());

                    $request->setResult([]);
                })
            )
        ;

        $action = new PayoutPayoutAction();
        $action->setGateway($gatewayMock);

        $action->execute($payout = new Payout($payoutModel));

        $this->assertSame($payoutModel, $payout->getFirstModel());
        $this->assertInstanceOf(ArrayAccess::class, $payout->getModel());
        $this->assertNull($payout->getToken());
    }

    public function testShouldSetConvertedResultToPayoutAsDetails(): void
    {
        $payoutModel = new PayoutModel();

        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->atLeast(2))
            ->method('execute')
            ->withConsecutive(
                [$this->isInstanceOf(GetHumanStatus::class)],
                [$this->isInstanceOf(Convert::class)]
            )
            ->willReturnOnConsecutiveCalls(
                $this->returnCallback(function (GetHumanStatus $request): void {
                    $request->markNew();
                }),
                $this->returnCallback(function (Convert $request): void {
                    $request->setResult([
                        'foo' => 'fooVal',
                    ]);
                })
            )
        ;

        $action = new PayoutPayoutAction();
        $action->setGateway($gatewayMock);

        $action->execute($payout = new Payout($payoutModel));

        $this->assertSame($payoutModel, $payout->getFirstModel());
        $this->assertInstanceOf(ArrayAccess::class, $payout->getModel());

        $details = $payoutModel->getDetails();
        $this->assertNotEmpty($details);

        $this->assertArrayHasKey('foo', $details);
        $this->assertSame('fooVal', $details['foo']);
    }

    public function testShouldExecuteConvertRequestWithTokenIfOnePresent(): void
    {
        $payoutModel = new PayoutModel();
        $token = $this->createTokenMock();

        $testCase = $this;

        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->atLeast(2))
            ->method('execute')
            ->withConsecutive([$this->isInstanceOf(GetHumanStatus::class)], [$this->isInstanceOf(Convert::class)])
            ->willReturnOnConsecutiveCalls(
                $this->returnCallback(function (GetHumanStatus $request): void {
                    $request->markNew();
                }),
                $this->returnCallback(function (Convert $request) use ($testCase, $payoutModel, $token): void {
                    $testCase->assertSame($payoutModel, $request->getSource());
                    $testCase->assertSame($token, $request->getToken());

                    $request->setResult([]);
                })
            )
        ;

        $action = new PayoutPayoutAction();
        $action->setGateway($gatewayMock);

        $payout = new Payout($token);
        $payout->setModel($payoutModel);

        $action->execute($payout);

        $this->assertSame($payoutModel, $payout->getFirstModel());
        $this->assertInstanceOf(ArrayAccess::class, $payout->getModel());
        $this->assertSame($token, $payout->getToken());
    }

    public function testShouldSetDetailsBackToPayoutAfterPayoutDetailsExecution(): void
    {
        $expectedDetails = [
            'foo' => 'fooVal',
        ];

        $payoutModel = new PayoutModel();
        $payoutModel->setDetails($expectedDetails);

        $testCase = $this;

        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->atLeast(2))
            ->method('execute')
            ->withConsecutive([$this->isInstanceOf(GetHumanStatus::class)], [$this->isInstanceOf(Payout::class)])
            ->willReturnOnConsecutiveCalls(
                $this->returnCallback(function (GetHumanStatus $request): void {
                    $request->markPending();
                }),
                $this->returnCallback(function (Payout $request) use ($testCase, $expectedDetails): void {
                    $details = $request->getModel();

                    $testCase->assertInstanceOf(ArrayAccess::class, $details);
                    $testCase->assertEquals($expectedDetails, iterator_to_array($details));

                    $details['bar'] = 'barVal';
                })
            )
        ;

        $action = new PayoutPayoutAction();
        $action->setGateway($gatewayMock);

        $action->execute($payout = new Payout($payoutModel));

        $this->assertSame($payoutModel, $payout->getFirstModel());
        $this->assertInstanceOf(ArrayAccess::class, $payout->getModel());
        $this->assertEquals([
            'foo' => 'fooVal',
            'bar' => 'barVal',
        ], $payoutModel->getDetails());
    }

    public function testShouldSetDetailsBackToPayoutEvenIfExceptionThrown(): void
    {
        $expectedDetails = [
            'foo' => 'fooVal',
        ];

        $payoutModel = new PayoutModel();
        $payoutModel->setDetails($expectedDetails);

        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->atLeast(2))
            ->method('execute')
            ->withConsecutive([$this->isInstanceOf(GetHumanStatus::class)], [$this->isInstanceOf(Payout::class)])
            ->willReturnOnConsecutiveCalls(
                $this->returnCallback(function (GetHumanStatus $request): void {
                    $request->markPending();
                }),
                $this->throwException(new Exception())
            )
        ;

        $action = new PayoutPayoutAction();
        $action->setGateway($gatewayMock);

        $this->expectException('Exception');
        $action->execute($payout = new Payout($payoutModel));

        $this->assertSame($payoutModel, $payout->getFirstModel());
        $this->assertInstanceOf(ArrayAccess::class, $payout->getModel());
        $this->assertEquals([
            'foo' => 'fooVal',
            'bar' => 'barVal',
        ], $payoutModel->getDetails());
    }
}
