<?php

namespace Payum\Core\Bridge\Symfony\Event;

use Payum\Core\Extension\Context;
use Symfony\Contracts\EventDispatcher\Event;

class ExecuteEvent extends Event
{
    /**
     * @var Context
     */
    protected $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}
