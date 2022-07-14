<?php

namespace Payum\Klarna\Checkout\Tests\Action;

use Klarna_Checkout_Order;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayInterface;
use Payum\Core\Model\Identity;
use Payum\Core\Model\Token;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\Authorize;
use Payum\Core\Request\RenderTemplate;
use Payum\Core\Request\Sync;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryInterface;
use Payum\Core\Security\TokenInterface;
use Payum\Klarna\Checkout\Action\AuthorizeAction;
use Payum\Klarna\Checkout\Config;
use Payum\Klarna\Checkout\Constants;
use Payum\Klarna\Checkout\Request\Api\CreateOrder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

class AuthorizeActionTest extends TestCase
{
    public function testShouldImplementActionInterface(): void
    {
        $rc = new ReflectionClass(AuthorizeAction::class);

        $this->assertTrue($rc->implementsInterface(ActionInterface::class));
    }

    public function testShouldImplementGatewayAwareInterface(): void
    {
        $rc = new ReflectionClass(AuthorizeAction::class);

        $this->assertTrue($rc->implementsInterface(GatewayAwareInterface::class));
    }

    public function testShouldImplementsGenericTokenFactoryAwareInterface(): void
    {
        $rc = new ReflectionClass(AuthorizeAction::class);

        $this->assertTrue($rc->implementsInterface(GenericTokenFactoryAwareInterface::class));
    }

    public function testShouldImplementsApiAwareInterface(): void
    {
        $rc = new ReflectionClass(AuthorizeAction::class);

        $this->assertTrue($rc->implementsInterface(ApiAwareInterface::class));
    }

    public function testShouldSupportAuthorizeWithArrayAsModel(): void
    {
        $action = new AuthorizeAction('aTemplate');

        $this->assertTrue($action->supports(new Authorize([])));
    }

    public function testShouldNotSupportAnythingNotAuthorize(): void
    {
        $action = new AuthorizeAction('aTemplate');

        $this->assertFalse($action->supports(new stdClass()));
    }

    public function testShouldNotSupportAuthorizeWithNotArrayAccessModel(): void
    {
        $action = new AuthorizeAction('aTemplate');

        $this->assertFalse($action->supports(new Authorize(new stdClass())));
    }

    public function testThrowIfNotSupportedRequestGivenAsArgumentOnExecute(): void
    {
        $this->expectException(RequestNotSupportedException::class);
        $action = new AuthorizeAction('aTemplate');

        $action->execute(new stdClass());
    }

    public function testShouldSubExecuteSyncIfModelHasLocationSet(): void
    {
        $this->expectException(HttpResponse::class);
        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->atLeast(2))
            ->method('execute')
            ->withConsecutive(
                [$this->isInstanceOf(Sync::class)],
                [$this->isInstanceOf(RenderTemplate::class)]
            )
        ;

        $action = new AuthorizeAction('aTemplate');
        $action->setGateway($gatewayMock);
        $action->setApi(new Config());

        $action->execute(new Authorize([
            'status' => Constants::STATUS_CHECKOUT_INCOMPLETE,
            'location' => 'aLocation',
            'merchant' => [
                'confirmation_uri' => 'theConfirmationUri',
                'push_uri' => 'thePushUri',
                'checkout_uri' => 'theCheckoutUri',
                'terms_uri' => 'theTermsUri',
            ],
        ]));
    }

    public function testShouldSubExecuteCreateOrderRequestIfStatusAndLocationNotSet(): void
    {
        $orderMock = $this->createOrderMock();
        $orderMock
            ->expects($this->once())
            ->method('marshal')
            ->willReturn([
                'foo' => 'fooVal',
                'bar' => 'barVal',
            ])
        ;
        $orderMock
            ->expects($this->once())
            ->method('getLocation')
            ->willReturn('theLocation')
        ;

        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->atLeast(2))
            ->method('execute')
            ->withConsecutive(
                [$this->isInstanceOf(CreateOrder::class)],
                [$this->isInstanceOf(Sync::class)]
            )
            ->willReturnOnConsecutiveCalls(
                $this->returnCallback(function (CreateOrder $request) use ($orderMock): void {
                    $request->setOrder($orderMock);
                })
            )
        ;

        $action = new AuthorizeAction('aTemplate');
        $action->setGateway($gatewayMock);
        $action->setApi(new Config());

        $model = new \ArrayObject([
            'merchant' => [
                'confirmation_uri' => 'theConfirmationUri',
                'push_uri' => 'thePushUri',
                'checkout_uri' => 'theCheckoutUri',
                'terms_uri' => 'theTermsUri',
            ],
        ]);

        $action->execute(new Authorize($model));

        $this->assertSame('fooVal', $model['foo']);
        $this->assertSame('barVal', $model['bar']);
        $this->assertSame('theLocation', $model['location']);
    }

    public function testShouldThrowReplyWhenStatusCheckoutIncomplete(): void
    {
        $snippet = 'theSnippet';
        $expectedContent = 'theTemplateContent';
        $expectedTemplateName = 'theTemplateName';
        $expectedContext = [
            'snippet' => $snippet,
        ];

        $testCase = $this;

        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->atLeast(2))
            ->method('execute')
            ->withConsecutive(
                [$this->isInstanceOf(Sync::class)],
                [$this->isInstanceOf(RenderTemplate::class)]
            )
            ->willReturnOnConsecutiveCalls(
                null,
                $this->returnCallback(function (RenderTemplate $request) use ($testCase, $expectedTemplateName, $expectedContext, $expectedContent): void {
                    $testCase->assertSame($expectedTemplateName, $request->getTemplateName());
                    $testCase->assertEquals($expectedContext, $request->getParameters());

                    $request->setResult($expectedContent);
                })
            )
        ;

        $action = new AuthorizeAction($expectedTemplateName);
        $action->setGateway($gatewayMock);

        try {
            $action->execute(new Authorize([
                'location' => 'aLocation',
                'status' => Constants::STATUS_CHECKOUT_INCOMPLETE,
                'gui' => [
                    'snippet' => $snippet,
                ],
                'merchant' => [
                    'confirmation_uri' => 'theConfirmationUri',
                    'push_uri' => 'thePushUri',
                    'checkout_uri' => 'theCheckoutUri',
                    'terms_uri' => 'theTermsUri',
                ],
            ]));
        } catch (HttpResponse $reply) {
            $this->assertSame($expectedContent, $reply->getContent());

            return;
        }

        $this->fail('Exception expected to be throw');
    }

    public function testShouldNotThrowReplyWhenStatusNotSet(): void
    {
        $action = new AuthorizeAction('aTemplate');
        $gateway = $this->createGatewayMock();
        $action->setGateway($gateway);

        $gateway->expects($this->once())
            ->method('execute')
            ->with(
                new Sync(ArrayObject::ensureArrayObject([
                    'location' => 'aLocation',
                    'gui' => [
                        'snippet' => 'theSnippet',
                    ],
                    'merchant' => [
                        'confirmation_uri' => 'theConfirmationUri',
                        'push_uri' => 'thePushUri',
                        'checkout_uri' => 'theCheckoutUri',
                        'terms_uri' => 'theTermsUri',
                    ],
                ]))
            );

        $action->execute(new Authorize([
            'location' => 'aLocation',
            'gui' => [
                'snippet' => 'theSnippet',
            ],
            'merchant' => [
                'confirmation_uri' => 'theConfirmationUri',
                'push_uri' => 'thePushUri',
                'checkout_uri' => 'theCheckoutUri',
                'terms_uri' => 'theTermsUri',
            ],
        ]));
    }

    public function testShouldNotThrowReplyWhenStatusCreated(): void
    {
        $action = new AuthorizeAction('aTemplate');
        $gateway = $this->createGatewayMock();
        $action->setGateway($gateway);

        $gateway->expects($this->once())
            ->method('execute')
            ->with(
                new Sync(ArrayObject::ensureArrayObject(
                    [
                        'location' => 'aLocation',
                        'status' => Constants::STATUS_CREATED,
                        'gui' => [
                            'snippet' => 'theSnippet',
                        ],
                        'merchant' => [
                            'confirmation_uri' => 'theConfirmationUri',
                            'push_uri' => 'thePushUri',
                            'checkout_uri' => 'theCheckoutUri',
                            'terms_uri' => 'theTermsUri',

                        ],
                    ]
                ))
            );

        $action->execute(new Authorize([
            'location' => 'aLocation',
            'status' => Constants::STATUS_CREATED,
            'gui' => [
                'snippet' => 'theSnippet',
            ],
            'merchant' => [
                'confirmation_uri' => 'theConfirmationUri',
                'push_uri' => 'thePushUri',
                'checkout_uri' => 'theCheckoutUri',
                'terms_uri' => 'theTermsUri',
            ],
        ]));
    }

    public function testShouldThrowIfPushUriNotSet(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The push_uri fields are required.');
        $action = new AuthorizeAction('aTemplate');
        $action->setGateway($this->createGatewayMock());

        $action->execute(new Authorize([
            'merchant' => [
                'confirmation_uri' => 'theConfirmationUri',
                'checkout_uri' => 'theCheckoutUri',
                'terms_uri' => 'theTermsUri',
            ],
        ]));
    }

    public function testShouldThrowIfConfirmUriNotSet(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The confirmation_uri fields are required.');
        $action = new AuthorizeAction('aTemplate');
        $action->setGateway($this->createGatewayMock());

        $action->execute(new Authorize([
            'merchant' => [
                'checkout_uri' => 'theCheckoutUri',
                'terms_uri' => 'theTermsUri',
                'push_uri' => 'thePushUri',
            ],
        ]));
    }

    public function testShouldThrowIfCheckoutUriNotSetNeitherInConfigNorPayment(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The checkout_uri fields are required.');
        $action = new AuthorizeAction('aTemplate');
        $action->setGateway($this->createGatewayMock());
        $action->setApi(new Config());

        $action->execute(new Authorize([
            'merchant' => [
                'confirmation_uri' => 'theConfirmationUri',
                'terms_uri' => 'theTermsUri',
                'push_uri' => 'thePushUri',
            ],
        ]));
    }

    public function testShouldUseCheckoutUriFromConfig(): void
    {
        $config = new Config();
        $config->checkoutUri = 'theCheckoutUrl';

        $action = new AuthorizeAction('aTemplate');
        $gateway = $this->createGatewayMock();
        $gateway->expects($this->once())
            ->method('execute')
            ->with(new Sync(ArrayObject::ensureArrayObject([
                'location' => 'aLocation',
                'merchant' => [
                    'checkout_uri' => 'theCheckoutUrl',
                    'confirmation_uri' => 'theConfirmationUri',
                    'terms_uri' => 'theTermsUri',
                    'push_uri' => 'thePushUri',

                ],
            ])));

        $action->setGateway($gateway);
        $action->setApi($config);

        $action->execute(new Authorize([
            'location' => 'aLocation',
            'merchant' => [
                'confirmation_uri' => 'theConfirmationUri',
                'terms_uri' => 'theTermsUri',
                'push_uri' => 'thePushUri',
            ],
        ]));
    }

    public function testShouldThrowIfTermsUriNotSetNeitherInConfigNorPayment(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The terms_uri fields are required.');
        $action = new AuthorizeAction('aTemplate');
        $action->setGateway($this->createGatewayMock());
        $action->setApi(new Config());

        $action->execute(new Authorize([
            'merchant' => [
                'confirmation_uri' => 'theConfirmationUri',
                'push_uri' => 'thePushUri',
                'checkout_uri' => 'theCheckoutUri',
            ],
        ]));
    }

    public function testShouldUseTermsUriFromConfig(): void
    {
        $config = new Config();
        $config->termsUri = 'theTermsUrl';

        $action = new AuthorizeAction('aTemplate');
        $gateway = $this->createGatewayMock();
        $action->setGateway($gateway);
        $action->setApi($config);

        $gateway->expects($this->once())
            ->method('execute')
            ->with(
                new Sync(ArrayObject::ensureArrayObject([
                    'location' => 'aLocation',
                    'merchant' => [
                        'confirmation_uri' => 'theConfirmationUri',
                        'checkout_uri' => 'theCheckoutUri',
                        'push_uri' => 'thePushUri',
                        'terms_uri' => 'theTermsUrl',
                    ],
                ]))
            );

        $action->execute(new Authorize([
            'location' => 'aLocation',
            'merchant' => [
                'confirmation_uri' => 'theConfirmationUri',
                'checkout_uri' => 'theCheckoutUri',
                'push_uri' => 'thePushUri',
            ],
        ]));
    }

    public function testShouldUseTargetUrlFromRequestTokenAsConfirmationIfNotSet(): void
    {
        $config = new Config();

        $action = new AuthorizeAction('aTemplate');
        $gateway = $this->createGatewayMock();
        $action->setGateway($gateway);
        $action->setApi($config);

        $token = new Token();
        $token->setTargetUrl('theTargetUrl');

        $gateway->expects($this->once())
            ->method('execute')
            ->with(
                new Sync(ArrayObject::ensureArrayObject([
                    'location' => 'aLocation',
                    'merchant' => [
                        'confirmation_uri' => 'theTargetUrl',
                        'checkout_uri' => 'theCheckoutUri',
                        'push_uri' => 'thePushUri',
                        'terms_uri' => 'theTermsUri',
                    ],
                ]))
            );

        $authorize = new Authorize($token);
        $authorize->setModel([
            'location' => 'aLocation',
            'merchant' => [
                'terms_uri' => 'theTermsUri',
                'checkout_uri' => 'theCheckoutUri',
                'push_uri' => 'thePushUri',
            ],
        ]);

        $action->execute($authorize);
    }

    public function testShouldGeneratePushUriIfNotSet(): void
    {
        $config = new Config();
        $config->termsUri = 'theTermsUri';

        $token = new Token();
        $token->setTargetUrl('theTargetUrl');
        $token->setGatewayName('theGatewayName');
        $token->setDetails($identity = new Identity('id', TokenInterface::class));

        $notifyToken = new Token();
        $notifyToken->setTargetUrl('theNotifyUrl');

        $tokenFactory = $this->createMock(GenericTokenFactoryInterface::class);
        $tokenFactory
            ->expects($this->once())
            ->method('createNotifyToken')
            ->with('theGatewayName', $this->identicalTo($identity))
            ->willReturn($notifyToken)
        ;

        $action = new AuthorizeAction('aTemplate');
        $action->setGateway($this->createGatewayMock());
        $action->setApi($config);
        $action->setGenericTokenFactory($tokenFactory);

        $authorize = new Authorize($token);
        $authorize->setModel([
            'location' => 'aLocation',
            'merchant' => [
                'confirmation_uri' => 'theConfirmationUri',
                'checkout_uri' => 'theCheckoutUri',
                'terms_uri' => 'theTermsUri',
            ],
        ]);

        $action->execute($authorize);
    }

    /**
     * @return MockObject|GatewayInterface
     */
    protected function createGatewayMock()
    {
        return $this->createMock(GatewayInterface::class);
    }

    /**
     * @return MockObject|Klarna_Checkout_Order
     */
    protected function createOrderMock()
    {
        return $this->createMock(Klarna_Checkout_Order::class);
    }
}
