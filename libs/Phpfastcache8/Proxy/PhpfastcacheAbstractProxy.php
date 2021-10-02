<?php

/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 *
 */
declare(strict_types=1);

namespace Phpfastcache\Proxy;

use BadMethodCallException;
use Phpfastcache\CacheManager;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Entities\DriverStatistic;
use Psr\Cache\CacheItemInterface;

/**
 * Class phpFastCache
 *
 * Handle methods using annotations for IDE
 * because they're handled by __call()
 * Check out ExtendedCacheItemInterface to see all
 * the drivers methods magically implemented
 *
 * @method ExtendedCacheItemInterface getItem($key) Retrieve an item and returns an empty item if not found
 * @method ExtendedCacheItemInterface[] getItems(array $keys) Retrieve an item and returns an empty item if not found
 * @method bool hasItem() hasItem($key) Tests if an item exists
 * @method bool deleteItem(string $key) Delete an item
 * @method bool deleteItems(array $keys) Delete some items
 * @method bool save(CacheItemInterface $item) Save an item
 * @method bool saveDeferred(CacheItemInterface $item) Sets a cache item to be persisted later
 * @method bool commit() Persists any deferred cache items
 * @method bool clear() Allow you to completely empty the cache and restart from the beginning
 * @method DriverStatistic stats() Returns a DriverStatistic object
 * @method ExtendedCacheItemInterface getItemsByTag($tagName) Return items by a tag
 * @method ExtendedCacheItemInterface[] getItemsByTags(array $tagNames) Return items by some tags
 * @method bool deleteItemsByTag($tagName) Delete items by a tag
 * @method bool deleteItemsByTags(array $tagNames) // Delete items by some tags
 * @method void incrementItemsByTag($tagName, $step = 1) // Increment items by a tag
 * @method void incrementItemsByTags(array $tagNames, $step = 1) // Increment items by some tags
 * @method void decrementItemsByTag($tagName, $step = 1) // Decrement items by a tag
 * @method void decrementItemsByTags(array $tagNames, $step = 1) // Decrement items by some tags
 * @method void appendItemsByTag($tagName, $data) // Append items by a tag
 * @method void appendItemsByTags(array $tagNames, $data) // Append items by a tags
 * @method void prependItemsByTag($tagName, $data) // Prepend items by a tag
 * @method void prependItemsByTags(array $tagNames, $data) // Prepend items by a tags
 */
abstract class PhpfastcacheAbstractProxy
{
    /**
     * @var ExtendedCacheItemPoolInterface
     */
    protected $instance;

    /**
     * PhpfastcacheAbstractProxy constructor.
     * @param string $driver
     * @param null $config
     * @throws \Phpfastcache\Exceptions\PhpfastcacheDriverCheckException
     * @throws \Phpfastcache\Exceptions\PhpfastcacheDriverException
     * @throws \Phpfastcache\Exceptions\PhpfastcacheDriverNotFoundException
     * @throws \Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException
     * @throws \Phpfastcache\Exceptions\PhpfastcacheInvalidConfigurationException
     * @throws \Phpfastcache\Exceptions\PhpfastcacheLogicException
     * @throws \ReflectionException
     */
    public function __construct(string $driver, $config = null)
    {
        $this->instance = CacheManager::getInstance($driver, $config);
    }

    /**
     * @param string $name
     * @param array $args
     * @return mixed
     * @throws BadMethodCallException
     */
    public function __call(string $name, array $args)
    {
        if (\method_exists($this->instance, $name)) {
            return $this->instance->$name(...$args);
        }

        throw new BadMethodCallException(\sprintf('Method %s does not exists', $name));
    }
}
