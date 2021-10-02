<?php
/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 *
 */

namespace phpFastCache\Core\Pool;

use phpFastCache\CacheManager;
use phpFastCache\Core\Item\ExtendedCacheItemInterface;
use phpFastCache\Entities\ItemBatch;
use phpFastCache\EventManager;
use phpFastCache\Exceptions\phpFastCacheCoreException;
use phpFastCache\Exceptions\phpFastCacheInvalidArgumentException;
use phpFastCache\Exceptions\phpFastCacheLogicException;
use phpFastCache\Util\ClassNamespaceResolverTrait;
use Psr\Cache\CacheItemInterface;

/**
 * Trait StandardPsr6StructureTrait
 * @package phpFastCache\Core
 * @property array $config The config array
 */
trait CacheItemPoolTrait
{
    use ClassNamespaceResolverTrait;

    /**
     * @var string
     */
    protected static $unsupportedKeyChars = '{}()/\@:';

    /**
     * @var array
     */
    protected $deferredList = [];

    /**
     * @var ExtendedCacheItemInterface[]
     */
    protected $itemInstances = [];

    /**
     * @var EventManager
     */
    protected $eventManager;

    /**
     * @param string $key
     * @return \phpFastCache\Core\Item\ExtendedCacheItemInterface
     * @throws phpFastCacheInvalidArgumentException
     * @throws phpFastCacheLogicException
     * @throws phpFastCacheCoreException
     */
    public function getItem($key)
    {
        if (is_string($key)) {
            /**
             * Replace array_key_exists by isset
             * due to performance issue on huge
             * loop dispatching operations
             */
            if (!isset($this->itemInstances[ $key ])) {
                if (preg_match('~([' . preg_quote(self::$unsupportedKeyChars, '~') . ']+)~', $key, $matches)) {
                    throw new phpFastCacheInvalidArgumentException('Unsupported key character detected: "' . $matches[ 1 ] . '". Please check: https://github.com/PHPSocialNetwork/phpfastcache/wiki/%5BV6%CB%96%5D-Unsupported-characters-in-key-identifiers');
                }

                /**
                 * @var $item ExtendedCacheItemInterface
                 */
                CacheManager::$ReadHits++;
                $cacheSlamsSpendSeconds = 0;
                $class = $this->getClassNamespace() . '\Item';
                $item = new $class($this, $key);
                $item->setEventManager($this->eventManager);

                getItemDriverRead:
                {
                    $driverArray = $this->driverRead($item);

                    if ($driverArray) {
                        if (!is_array($driverArray)) {
                            throw new phpFastCacheCoreException(sprintf('The driverRead method returned an unexpected variable type: %s',
                              gettype($driverArray)));
                        }
                        $driverData = $this->driverUnwrapData($driverArray);

                        if ($this->getConfig()[ 'preventCacheSlams' ]) {
                            while ($driverData instanceof ItemBatch) {
                                if ($driverData->getItemDate()->getTimestamp() + $this->getConfig()[ 'cacheSlamsTimeout' ] < time()) {
                                    /**
                                     * The timeout has been reached
                                     * Consider that the batch has
                                     * failed and serve an empty item
                                     * to avoid to get stuck with a
                                     * batch item stored in driver
                                     */
                                    goto getItemDriverExpired;
                                }
                                /**
                                 * @eventName CacheGetItem
                                 * @param $this ExtendedCacheItemPoolInterface
                                 * @param $driverData ItemBatch
                                 * @param $cacheSlamsSpendSeconds int
                                 */
                                $this->eventManager->dispatch('CacheGetItemInSlamBatch', $this, $driverData, $cacheSlamsSpendSeconds);

                                /**
                                 * Wait for a second before
                                 * attempting to get exit
                                 * the current batch process
                                 */
                                sleep(1);
                                $cacheSlamsSpendSeconds++;
                                goto getItemDriverRead;
                            }
                        }

                        $item->set($driverData);
                        $item->expiresAt($this->driverUnwrapEdate($driverArray));

                        if ($this->config[ 'itemDetailedDate' ]) {

                            /**
                             * If the itemDetailedDate has been
                             * set after caching, we MUST inject
                             * a new DateTime object on the fly
                             */
                            $item->setCreationDate($this->driverUnwrapCdate($driverArray) ?: new \DateTime());
                            $item->setModificationDate($this->driverUnwrapMdate($driverArray) ?: new \DateTime());
                        }

                        $item->setTags($this->driverUnwrapTags($driverArray));

                        getItemDriverExpired:
                        if ($item->isExpired()) {
                            /**
                             * Using driverDelete() instead of delete()
                             * to avoid infinite loop caused by
                             * getItem() call in delete() method
                             * As we MUST return an item in any
                             * way, we do not de-register here
                             */
                            $this->driverDelete($item);

                            /**
                             * Reset the Item
                             */
                            $item->set(null)
                              ->expiresAfter(abs((int)$this->getConfig()[ 'defaultTtl' ]))
                              ->setHit(false)
                              ->setTags([]);
                            if ($this->config[ 'itemDetailedDate' ]) {

                                /**
                                 * If the itemDetailedDate has been
                                 * set after caching, we MUST inject
                                 * a new DateTime object on the fly
                                 */
                                $item->setCreationDate(new \DateTime());
                                $item->setModificationDate(new \DateTime());
                            }
                        } else {
                            $item->setHit(true);
                        }
                    } else {
                        $item->expiresAfter(abs((int)$this->getConfig()[ 'defaultTtl' ]));
                    }
                }
            }
        } else {
            throw new phpFastCacheInvalidArgumentException(sprintf('$key must be a string, got type "%s" instead.', gettype($key)));
        }

        /**
         * @eventName CacheGetItem
         * @param $this ExtendedCacheItemPoolInterface
         * @param $this ExtendedCacheItemInterface
         */
        $this->eventManager->dispatch('CacheGetItem', $this, $this->itemInstances[ $key ]);

        return $this->itemInstances[ $key ];
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return $this
     * @throws phpFastCacheInvalidArgumentException
     */
    public function setItem(CacheItemInterface $item)
    {
        if ($this->getClassNamespace() . '\\Item' === get_class($item)) {
            $this->itemInstances[ $item->getKey() ] = $item;

            return $this;
        } else {
            throw new phpFastCacheInvalidArgumentException(sprintf('Invalid Item Class "%s" for this driver.', get_class($item)));
        }
    }

    /**
     * @param array $keys
     * @return CacheItemInterface[]
     * @throws phpFastCacheInvalidArgumentException
     */
    public function getItems(array $keys = [])
    {
        $collection = [];
        foreach ($keys as $key) {
            $collection[ $key ] = $this->getItem($key);
        }

        return $collection;
    }

    /**
     * @param string $key
     * @return bool
     * @throws phpFastCacheInvalidArgumentException
     */
    public function hasItem($key)
    {
        return $this->getItem($key)->isHit();
    }

    /**
     * @return bool
     */
    public function clear()
    {
        /**
         * @eventName CacheClearItem
         * @param $this ExtendedCacheItemPoolInterface
         * @param $deferredList ExtendedCacheItemInterface[]
         */
        $this->eventManager->dispatch('CacheClearItem', $this, $this->itemInstances);

        CacheManager::$WriteHits++;
        $this->itemInstances = [];

        return $this->driverClear();
    }

    /**
     * @param string $key
     * @return bool
     * @throws phpFastCacheInvalidArgumentException
     */
    public function deleteItem($key)
    {
        $item = $this->getItem($key);
        if ($item->isHit() && $this->driverDelete($item)) {
            $item->setHit(false);
            CacheManager::$WriteHits++;

            /**
             * @eventName CacheCommitItem
             * @param $this ExtendedCacheItemPoolInterface
             * @param $item ExtendedCacheItemInterface
             */
            $this->eventManager->dispatch('CacheDeleteItem', $this, $item);

            /**
             * De-register the item instance
             * then collect gc cycles
             */
            $this->deregisterItem($key);

            /**
             * Perform a tag cleanup to avoid memory leaks
             */
            if (strpos($key, self::DRIVER_TAGS_KEY_PREFIX) !== 0) {
                $this->cleanItemTags($item);
            }

            return true;
        }

        return false;
    }

    /**
     * @param array $keys
     * @return bool
     * @throws phpFastCacheInvalidArgumentException
     */
    public function deleteItems(array $keys)
    {
        $return = null;
        foreach ($keys as $key) {
            $result = $this->deleteItem($key);
            if ($result !== false) {
                $return = $result;
            }
        }

        return (bool)$return;
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
     * @throws phpFastCacheInvalidArgumentException
     * @throws \RuntimeException
     */
    public function save(CacheItemInterface $item)
    {
        /**
         * @var ExtendedCacheItemInterface $item
         *
         * Replace array_key_exists by isset
         * due to performance issue on huge
         * loop dispatching operations
         */
        if (!isset($this->itemInstances[ $item->getKey() ])) {
            $this->itemInstances[ $item->getKey() ] = $item;
        } else if (spl_object_hash($item) !== spl_object_hash($this->itemInstances[ $item->getKey() ])) {
            throw new \RuntimeException('Spl object hash mismatches ! You probably tried to save a detached item which has been already retrieved from cache.');
        }

        /**
         * @eventName CacheSaveItem
         * @param $this ExtendedCacheItemPoolInterface
         * @param $this ExtendedCacheItemInterface
         */
        $this->eventManager->dispatch('CacheSaveItem', $this, $item);


        if ($this->getConfig()[ 'preventCacheSlams' ]) {
            /**
             * @var $itemBatch ExtendedCacheItemInterface
             */
            $class = new \ReflectionClass((new \ReflectionObject($this))->getNamespaceName() . '\Item');
            $itemBatch = $class->newInstanceArgs([$this, $item->getKey()]);
            $itemBatch->setEventManager($this->eventManager)
              ->set(new ItemBatch($item->getKey(), new \DateTime()))
              ->expiresAfter($this->getConfig()[ 'cacheSlamsTimeout' ]);

            /**
             * To avoid SPL mismatches
             * we have to re-attach the
             * original item to the pool
             */
            $this->driverWrite($itemBatch);
            $this->detachItem($itemBatch);
            $this->attachItem($item);
        }


        if ($this->driverWrite($item) && $this->driverWriteTags($item)) {
            $item->setHit(true);
            CacheManager::$WriteHits++;

            return true;
        }

        return false;
    }


    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return \Psr\Cache\CacheItemInterface
     * @throws \RuntimeException
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        if (!array_key_exists($item->getKey(), $this->itemInstances)) {
            $this->itemInstances[ $item->getKey() ] = $item;
        } else if (spl_object_hash($item) !== spl_object_hash($this->itemInstances[ $item->getKey() ])) {
            throw new \RuntimeException('Spl object hash mismatches ! You probably tried to save a detached item which has been already retrieved from cache.');
        }

        /**
         * @eventName CacheSaveDeferredItem
         * @param $this ExtendedCacheItemPoolInterface
         * @param $this ExtendedCacheItemInterface
         */
        $this->eventManager->dispatch('CacheSaveDeferredItem', $this, $item);

        return $this->deferredList[ $item->getKey() ] = $item;
    }

    /**
     * @return mixed|null
     * @throws phpFastCacheInvalidArgumentException
     */
    public function commit()
    {
        /**
         * @eventName CacheCommitItem
         * @param $this ExtendedCacheItemPoolInterface
         * @param $deferredList ExtendedCacheItemInterface[]
         */
        $this->eventManager->dispatch('CacheCommitItem', $this, $this->deferredList);

        $return = null;
        foreach ($this->deferredList as $key => $item) {
            $result = $this->save($item);
            if ($return !== false) {
                unset($this->deferredList[ $key ]);
                $return = $result;
            }
        }

        return (bool)$return;
    }
}
