<?php

namespace mpyw\LaravelTransactionObserver;

use Illuminate\Database\Connection;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\Events\TransactionRolledBack;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use mpyw\LaravelTransactionObserver\Events\DelayedCall;

/**
 * Class Provider
 */
class Provider extends ServiceProvider
{
    /**
     * @var array
     */
    protected $connectionEvents = [
        'begin' => TransactionBeginning::class,
        'commit' => TransactionCommitted::class,
        'rollback' => TransactionRolledBack::class,
    ];

    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @var Observer
     */
    protected $observer;

    /**
     * @var Connection
     */
    protected $db;

    /**
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Observer::class, function ($app) {
            return new Observer($app['events']);
        });
    }

    /**
     * @return void
     */
    public function boot()
    {
        $this->db = $this->app['db'];
        $this->events = $this->app['events'];
        $this->observer = $this->app[Observer::class];

        if ($this->db->transactionLevel() !== 0) {
            throw new \UnexpectedValueException('Initial transaction level must be zero'); // @codeCoverageIgnore
        }

        $this->listenConnectionEvents();
        $this->listenPresetEvents();
    }

    /**
     * @return void
     */
    protected function listenConnectionEvents()
    {
        foreach ($this->connectionEvents as $observerMethod => $connectionEvent) {
            $this->events->listen($connectionEvent, function () use ($observerMethod) {
                $this->observer->$observerMethod();
            });
        }
    }

    /**
     * @return void
     */
    protected function listenPresetEvents()
    {
        $this->observer->listen(DelayedCall::class);
    }
}
