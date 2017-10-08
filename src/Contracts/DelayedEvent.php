<?php

namespace mpyw\LaravelTransactionObserver\Contracts;

/**
 * Interface DelayedEvent
 *
 * Interface for delayed events.
 */
interface DelayedEvent
{
    /**
     * Fire event.
     *
     * @return void
     */
    public function fire();
}
