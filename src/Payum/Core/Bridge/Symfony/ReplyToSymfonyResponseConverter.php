<?php

namespace Payum\Core\Bridge\Symfony;

use Payum\Core\Bridge\Symfony\Reply\HttpResponse as SymfonyHttpResponse;
use Payum\Core\Exception\LogicException;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Reply\ReplyInterface;
use ReflectionObject;
use Symfony\Component\HttpFoundation\Response;

class ReplyToSymfonyResponseConverter
{
    public function convert(ReplyInterface $reply): Response
    {
        if ($reply instanceof SymfonyHttpResponse) {
            return $reply->getResponse();
        } elseif ($reply instanceof HttpResponse) {
            $headers = $reply->getHeaders();
            $headers['X-Status-Code'] = $reply->getStatusCode();

            return new Response($reply->getContent(), $reply->getStatusCode(), $headers);
        }

        $ro = new ReflectionObject($reply);

        throw new LogicException(
            sprintf('Cannot convert reply %s to http response.', $ro->getShortName()),
            0,
            $reply
        );
    }
}
