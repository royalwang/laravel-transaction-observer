<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use mpyw\LaravelTransactionObserver\Events\DelayedCall;
use mpyw\LaravelTransactionObserver\Facades\TransactionObserver;

class TransactionTest extends TestCase
{
    protected $assertions = [];

    protected function createEvent($value)
    {
        return new DelayedCall(function () use ($value) {
            $this->assertions[] = $value;
        });
    }

    /**
     * @test
     */
    public function should_dispatch_after_all_transactions_done()
    {
        $this->assertions[] = 1;
        Event::dispatch($this->createEvent(2));
        DB::transaction(function () {
            $this->assertions[] = 3;
            Event::dispatch($this->createEvent(8));
            $this->assertions[] = 4;
            try {
                DB::transaction(function () {
                    Event::dispatch($this->createEvent(null));
                    $this->assertions[] = 5;
                    Event::dispatch($this->createEvent(null));
                    throw new \Exception;
                });
            } catch (\Exception $e) {
                $this->assertions[] = 6;
                Event::dispatch($this->createEvent(9));
            }
            DB::transaction(function () {
                Event::dispatch($this->createEvent(10));
                $this->assertions[] = 7;
            });
        });

        $this->assertEquals($this->assertions, range(1, 10));
    }

    /**
     * @test
     */
    public function should_retrieve_callback_from_event()
    {
        $this->assertions[] = 1;
        $fn = function () {};
        $call = new DelayedCall($fn);
        $this->assertSame($fn, $call->getCallback());
    }

    /**
     * @test
     * @expectedException \BadMethodCallException
     */
    public function should_throw_exception_in_invalid_state()
    {
        TransactionObserver::commit();
    }


    /**
     * @test
     */
    public function should_forget_delayed_events()
    {
        TransactionObserver::forget(DelayedCall::class);

        $this->assertions[] = 1;
        Event::dispatch($this->createEvent(null));
        DB::transaction(function () {
            $this->assertions[] = 2;
            Event::dispatch($this->createEvent(null));
            $this->assertions[] = 3;
            try {
                DB::transaction(function () {
                    Event::dispatch($this->createEvent(null));
                    $this->assertions[] = 4;
                    Event::dispatch($this->createEvent(null));
                    throw new \Exception;
                });
            } catch (\Exception $e) {
                $this->assertions[] = 5;
                Event::dispatch($this->createEvent(null));
            }
            DB::transaction(function () {
                Event::dispatch($this->createEvent(null));
                $this->assertions[] = 6;
            });
        });

        $this->assertEquals($this->assertions, range(1, 6));
    }
}
