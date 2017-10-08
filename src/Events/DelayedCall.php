<?php

namespace mpyw\LaravelTransactionObserver\Events;

use mpyw\LaravelTransactionObserver\Contracts\DelayedEvent;

/**
 * Class DelayedCall
 *
 * Delayed callback event.
 */
class DelayedCall implements DelayedEvent
{
    /**
     * @var callable
     */
    protected $callback;

    /**
     * DelayedCall constructor.
     *
     * @param callable $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Retrieve callback.
     *
     * @return callable
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * Fire callback.
     *
     * @return void
     */
    public function fire()
    {
        $callback = $this->callback;
        $callback();
    }
}
