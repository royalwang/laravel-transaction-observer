<?php

namespace mpyw\LaravelTransactionObserver\Internal;

/**
 * Class Transaction
 *
 * Nestable transaction storage.
 */
class Transaction extends \SplObjectStorage
{
    /**
     * @var static|null
     */
    protected $parent;

    /**
     * Create new transaction to return it
     *
     * @return static
     */
    public function newTransaction()
    {
        $new = new static;
        parent::attach($new);
        $new->parent = $this;
        return $new;
    }

    /**
     * Return parent transaction keeping children.
     *
     * @return static
     */
    public function getParentKeepingChildren()
    {
        return $this->parent;
    }

    /**
     * Return parent transaction flushing children.
     *
     * @return static
     */
    public function getParentFlushingChildren()
    {
        $this->removeAll($this);
        return $this->parent;
    }

    /**
     * Flatten current and descendant transactions.
     *
     * @return array
     */
    public function flatten()
    {
        return static::flattenRecursive($this);
    }

    /**
     * @param Transaction|static $iter
     * @return array
     */
    protected static function flattenRecursive(Transaction $iter)
    {
        $flattened = [];
        foreach ($iter as $child) {
            $flattened = array_merge($flattened, $child instanceof static ? static::flattenRecursive($child) : [$child]);
        }
        return $flattened;
    }
}
