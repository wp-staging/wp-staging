<?php

namespace WPStaging\Vendor\GuzzleHttp\Promise;

final class Is
{
    /**
     * Returns true if a promise is pending.
     *
     * @return bool
     */
    public static function pending(\WPStaging\Vendor\GuzzleHttp\Promise\PromiseInterface $promise)
    {
        return $promise->getState() === \WPStaging\Vendor\GuzzleHttp\Promise\PromiseInterface::PENDING;
    }
    /**
     * Returns true if a promise is fulfilled or rejected.
     *
     * @return bool
     */
    public static function settled(\WPStaging\Vendor\GuzzleHttp\Promise\PromiseInterface $promise)
    {
        return $promise->getState() !== \WPStaging\Vendor\GuzzleHttp\Promise\PromiseInterface::PENDING;
    }
    /**
     * Returns true if a promise is fulfilled.
     *
     * @return bool
     */
    public static function fulfilled(\WPStaging\Vendor\GuzzleHttp\Promise\PromiseInterface $promise)
    {
        return $promise->getState() === \WPStaging\Vendor\GuzzleHttp\Promise\PromiseInterface::FULFILLED;
    }
    /**
     * Returns true if a promise is rejected.
     *
     * @return bool
     */
    public static function rejected(\WPStaging\Vendor\GuzzleHttp\Promise\PromiseInterface $promise)
    {
        return $promise->getState() === \WPStaging\Vendor\GuzzleHttp\Promise\PromiseInterface::REJECTED;
    }
}
