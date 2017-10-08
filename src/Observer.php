<?php

namespace mpyw\LaravelTransactionObserver;

use Illuminate\Contracts\Events\Dispatcher;
use mpyw\LaravelTransactionObserver\Contracts\DelayedEvent;
use mpyw\LaravelTransactionObserver\Internal\Transaction;

/**
 * Class Observer
 *
 * Observe and fire delayed events.
 */
class Observer
{
    /**
     * @var Transaction|null
     */
    protected $current;

    /**
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * TransactionObserver constructor.
     *
     * @param Dispatcher $dispatcher
     */
    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Register events.
     *
     * @param array|string $events
     */
    public function listen($events)
    {
        $this->dispatcher->listen($events, function (DelayedEvent $event) {
            $this->dispatch($event);
        });
    }

    /**
     * Unregister event.
     *
     * @param string $event
     */
    public function forget($event)
    {
        $this->dispatcher->forget($event);
    }

    /**
     * Should be called after transaction started.
     *
     * @return void
     */
    public function begin()
    {
        $this->current = $this->current instanceof Transaction
            ? $this->current->newTransaction()
            : new Transaction;
    }

    /**
     * Should be called after transaction committed.
     *
     * @return void
     */
    public function commit()
    {
        $this->assert();

        $current = $this->current;
        $this->current = $current->getParentKeepingChildren();

        if (!$this->current instanceof Transaction) {
            // There are no transactions, dispatch all reserved payloads
            foreach ($current->flatten() as $payload) {
                $payload->fire();
            }
        }
    }

    /**
     * Should be called after transaction rolled back.
     *
     * @return void
     */
    public function rollBack()
    {
        $this->assert();

        $this->current = $this->current->getParentFlushingChildren();
    }

    /**
     * Dispaches payload.
     *
     * @param DelayedEvent $event
     * @return void
     */
    protected function dispatch(DelayedEvent $event)
    {
        if ($this->current instanceof Transaction) {
            // Already in transaction, reserve it
            $this->current->attach($event);
        } else {
            // There are no transactions, just dispatch it
            $event->fire();
        }
    }

    /**
     * @return void
     */
    protected function assert()
    {
        if (!$this->current instanceof Transaction) {
            throw new \BadMethodCallException('Invalid state.');
        }
    }
}
