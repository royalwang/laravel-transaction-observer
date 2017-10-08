# Laravel Transaction Observer [![Build Status](https://travis-ci.org/mpyw/laravel-transaction-observer.svg?branch=master)](https://travis-ci.org/mpyw/laravel-transaction-observer) [![Coverage Status](https://coveralls.io/repos/github/mpyw/laravel-transaction-observer/badge.svg?branch=master)](https://coveralls.io/github/mpyw/laravel-transaction-observer?branch=master) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mpyw/laravel-transaction-observer/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mpyw/laravel-transaction-observer/?branch=master)

Observe delayed events and fire them after ALL transactions are done.

## Requirements

- PHP: ^5.5 || ^7.0
- Laravel: ^5.4

## Installing

```
composer require mpyw/laravel-transaction-observer:^1.0
```

## Basic Usage

Register service provider.

`config/app.php`:

```php
        /*
         * Package Service Providers...
         */
        mpyw\LaravelTransactionObserver\Provider::class,
```

That's all. Now you can dispatch `DelayedCall` that takes callback as the first argument.  

```php
public mpyw\LaravelTransactionObserver\Events\DelayedCall::__construct(callable $callback)
public void mpyw\LaravelTransactionObserver\Events\DelayedCall::fire()
```

Note that the callback is:

- Fired when **ALL** transactions are done.
- Canceled if current transaction failed.

```php
/**
 * Example: Handling callbacks for counter caching
 */

use mpyw\LaravelTransactionObserver\Events\DelayedCall;

DB::transaction(function () {

    $post = Post::create([
        'text' => 'This is main text',
        'comment_count' => 0,
    ]);

    DB::transaction(function () use ($post) {
        $comment = new Comment(['text' => 'This is first comment']);
        $comment->post()->associate($post);
        $comment->save();

        event(new DelayedCall(function () use ($post) {
            ++$post->comment_count; // A: Increment counter cache!
        }));
    });

    DB::transaction(function () use ($post) {
        $comment = new Comment(['text' => 'This is second comment']);
        $comment->post()->associate($post);
        $comment->save();

        event(new DelayedCall(function () use ($post) {
            ++$post->comment_count; // B: Increment counter cache!
        }));

        throw new \RuntimeException('Oops!');
    });
});

// A fires here, while B never do.
```

## Advanced Usage: Prepare custom Event classes

### 1. Make your class that implements `DelayedEvent`.

`app/Events/MyDelayedEvent.php`:

```php
<?php

namespace App\Events;

use mpyw\LaravelTransactionObserver\Contracts\DelayedEvent;

class MyDelayedEvent implements DelayedEvent
{
    protected $payload;

    public function __construct($payload)
    {
        $this->payload = $payload;
    }

    public function fire()
    {
        ($this->payload)();
    }
}
```

### 2. Listen it in your application service provider.

`app/Providers/AppServiceProvider.php`:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use mpyw\LaravelTransactionObserver\Facades\TransactionObserver;
use App\Events\MyDelayedEvent;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        TransactionObserver::listen(MyDelayedEvent::class);
    }
}
```

## Related Packages

- [mpyw/laravel-delayed-counter-cache](https://github.com/mpyw/laravel-delayed-counter-cache): Delayed counter cache incremented/decremented out of transactions
