<?php

namespace mpyw\LaravelTransactionObserver\Facades;

use Illuminate\Support\Facades\Facade;
use mpyw\LaravelTransactionObserver\Observer;

/**
 * Class TransactionObserver
 *
 * @method static void listen(array|string $events) Register events.
 * @method static void forget(string $event) Unregister event.
 */
class TransactionObserver extends Facade
{
    public static function getFacadeAccessor()
    {
        return Observer::class;
    }
}
