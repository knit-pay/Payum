<?php

namespace Payum\Klarna\Checkout\Tests\Action;

use Iterator;
use Payum\Core\Model\Payment;
use Payum\Core\Model\PaymentInterface;
use Payum\Core\Request\Convert;
use Payum\Core\Request\Generic;
use Payum\Core\Security\TokenInterface;
use Payum\Core\Tests\GenericActionTest;
use Payum\Klarna\Checkout\Action\ConvertPaymentAction;
use stdClass;

class ConvertPaymentActionTest extends GenericActionTest
{
    /**
     * @var class-string<ConvertPaymentAction>
     */
    protected $actionClass = ConvertPaymentAction::class;

    /**
     * @var class-string<Convert>
     */
    protected $requestClass = Convert::class;

    /**
     * @return \Iterator<Convert[]>
     */
    public function provideSupportedRequests(): Iterator
    {
        yield [new $this->requestClass(new Payment(), 'array')];
        yield [new $this->requestClass($this->createMock(PaymentInterface::class), 'array')];
        yield [new $this->requestClass(new Payment(), 'array', $this->createMock(TokenInterface::class))];
    }

    public function provideNotSupportedRequests(): Iterator
    {
        yield ['foo'];
        yield [['foo']];
        yield [new stdClass()];
        yield [$this->getMockForAbstractClass(Generic::class, [[]])];
        yield [new $this->requestClass(new stdClass(), 'array')];
        yield [new $this->requestClass(new Payment(), 'foobar')];
        yield [new $this->requestClass($this->createMock(PaymentInterface::class), 'foobar')];
    }

    public function testShouldCorrectlyConvertOrderToDetailsAndSetItBack(): void
    {
        $payment = new Payment();
        $payment->setNumber('theNumber');
        $payment->setCurrencyCode('SEK');
        $payment->setTotalAmount(123);
        $payment->setDescription('the description');
        $payment->setClientId('theClientId');
        $payment->setClientEmail('theClientEmail');

        $action = new ConvertPaymentAction();

        $action->execute($convert = new Convert($payment, 'array'));

        $details = $convert->getResult();

        $this->assertEquals([
            'cart' => [
                'items' => [
                    [
                        'reference' => 'theNumber',
                        'name' => 'theNumber',
                        'quantity' => 1,
                        'unit_price' => 164.0,
                        'discount_rate' => 0,
                        'tax_rate' => 2500,
                    ],
                ],
            ],
            'purchase_country' => 'SE',
            'purchase_currency' => 'SEK',
            'locale' => 'sv-se',

        ], $details);
    }

    public function testShouldDoNothingIfCurrencyNotSEK(): void
    {
        $payment = new Payment();
        $payment->setNumber('theNumber');
        $payment->setCurrencyCode('USD');
        $payment->setTotalAmount(123);
        $payment->setDescription('the description');
        $payment->setClientId('theClientId');
        $payment->setClientEmail('theClientEmail');

        $action = new ConvertPaymentAction();

        $action->execute($convert = new Convert($payment, 'array'));

        $details = $convert->getResult();

        $this->assertEquals([], $details);
    }

    public function testShouldNotOverwriteAlreadySetExtraDetails(): void
    {
        $payment = new Payment();
        $payment->setCurrencyCode('USD');
        $payment->setTotalAmount(123);
        $payment->setDescription('the description');
        $payment->setDetails([
            'foo' => 'fooVal',
        ]);

        $action = new ConvertPaymentAction();

        $action->execute($convert = new Convert($payment, 'array'));

        $details = $convert->getResult();

        $this->assertNotEmpty($details);

        $this->assertArrayHasKey('foo', $details);
        $this->assertSame('fooVal', $details['foo']);
    }
}
