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

namespace phpFastCache\Drivers\Cassandra;

use Cassandra;
use Cassandra\Session as CassandraSession;
use phpFastCache\Core\Pool\DriverBaseTrait;
use phpFastCache\Core\Pool\ExtendedCacheItemPoolInterface;
use phpFastCache\Entities\DriverStatistic;
use phpFastCache\Exceptions\phpFastCacheDriverCheckException;
use phpFastCache\Exceptions\phpFastCacheDriverException;
use phpFastCache\Exceptions\phpFastCacheInvalidArgumentException;
use phpFastCache\Exceptions\phpFastCacheLogicException;
use Psr\Cache\CacheItemInterface;

/**
 * Class Driver
 * @package phpFastCache\Drivers
 * @property CassandraSession $instance Instance of driver service
 */
class Driver implements ExtendedCacheItemPoolInterface
{
    const CASSANDRA_KEY_SPACE = 'phpfastcache';
    const CASSANDRA_TABLE     = 'cacheItems';

    use DriverBaseTrait;

    /**
     * Driver constructor.
     * @param array $config
     * @throws phpFastCacheDriverException
     */
    public function __construct(array $config = [])
    {
        $this->setup($config);

        if (!$this->driverCheck()) {
            throw new phpFastCacheDriverCheckException(sprintf(self::DRIVER_CHECK_FAILURE, $this->getDriverName()));
        } else {
            $this->driverConnect();
        }
    }

    /**
     * @return bool
     */
    public function driverCheck()
    {
        return extension_loaded('Cassandra') && class_exists(\Cassandra::class);
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
     * @throws phpFastCacheInvalidArgumentException
     */
    protected function driverWrite(CacheItemInterface $item)
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            try {
                $cacheData = $this->encode($this->driverPreWrap($item));
                $options = new Cassandra\ExecutionOptions([
                  'arguments' => [
                    'cache_uuid' => new Cassandra\Uuid(),
                    'cache_id' => $item->getKey(),
                    'cache_data' => $cacheData,
                    'cache_creation_date' => new Cassandra\Timestamp((new \DateTime())->getTimestamp()),
                    'cache_expiration_date' => new Cassandra\Timestamp($item->getExpirationDate()->getTimestamp()),
                    'cache_length' => strlen($cacheData),
                  ],
                  'consistency' => Cassandra::CONSISTENCY_ALL,
                  'serial_consistency' => Cassandra::CONSISTENCY_SERIAL,
                ]);

                $query = sprintf('INSERT INTO %s.%s
                    (
                      cache_uuid, 
                      cache_id, 
                      cache_data, 
                      cache_creation_date, 
                      cache_expiration_date,
                      cache_length
                    )
                  VALUES (:cache_uuid, :cache_id, :cache_data, :cache_creation_date, :cache_expiration_date, :cache_length);
            ', self::CASSANDRA_KEY_SPACE, self::CASSANDRA_TABLE);

                $result = $this->instance->execute(new Cassandra\SimpleStatement($query), $options);
                /**
                 * There's no real way atm
                 * to know if the item has
                 * been really upserted
                 */
                return $result instanceof Cassandra\Rows;
            } catch (\Cassandra\Exception\InvalidArgumentException $e) {
                throw new phpFastCacheInvalidArgumentException($e, 0, $e);
            }
        } else {
            throw new phpFastCacheInvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return null|array
     */
    protected function driverRead(CacheItemInterface $item)
    {
        try {
            $options = new Cassandra\ExecutionOptions([
              'arguments' => ['cache_id' => $item->getKey()],
              'page_size' => 1,
            ]);
            $query = sprintf(
              'SELECT cache_data FROM %s.%s WHERE cache_id = :cache_id;',
              self::CASSANDRA_KEY_SPACE,
              self::CASSANDRA_TABLE
            );
            $results = $this->instance->execute(new Cassandra\SimpleStatement($query), $options);

            if ($results instanceof Cassandra\Rows && $results->count() === 1) {
                return $this->decode($results->first()[ 'cache_data' ]);
            } else {
                return null;
            }
        } catch (Cassandra\Exception $e) {
            return null;
        }
    }

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return bool
     * @throws phpFastCacheInvalidArgumentException
     */
    protected function driverDelete(CacheItemInterface $item)
    {
        /**
         * Check for Cross-Driver type confusion
         */
        if ($item instanceof Item) {
            try {
                $options = new Cassandra\ExecutionOptions([
                  'arguments' => [
                    'cache_id' => $item->getKey(),
                  ],
                ]);
                $result = $this->instance->execute(new Cassandra\SimpleStatement(sprintf(
                  'DELETE FROM %s.%s WHERE cache_id = :cache_id;',
                  self::CASSANDRA_KEY_SPACE,
                  self::CASSANDRA_TABLE
                )), $options);

                /**
                 * There's no real way atm
                 * to know if the item has
                 * been really deleted
                 */
                return $result instanceof Cassandra\Rows;
            } catch (Cassandra\Exception $e) {
                return false;
            }
        } else {
            throw new phpFastCacheInvalidArgumentException('Cross-Driver type confusion detected');
        }
    }

    /**
     * @return bool
     */
    protected function driverClear()
    {
        try {
            $this->instance->execute(new Cassandra\SimpleStatement(sprintf(
              'TRUNCATE %s.%s;',
              self::CASSANDRA_KEY_SPACE, self::CASSANDRA_TABLE
            )));

            return true;
        } catch (Cassandra\Exception $e) {
            return false;
        }
    }

    /**
     * @return bool
     * @throws phpFastCacheLogicException
     * @throws \Cassandra\Exception
     */
    protected function driverConnect()
    {
        if ($this->instance instanceof CassandraSession) {
            throw new phpFastCacheLogicException('Already connected to Couchbase server');
        } else {
            $host = isset($this->config[ 'host' ]) ? $this->config[ 'host' ] : '127.0.0.1';
            $port = isset($this->config[ 'port' ]) ? $this->config[ 'port' ] : 9042;
            $timeout = isset($this->config[ 'timeout' ]) ? $this->config[ 'timeout' ] : 2;
            $password = isset($this->config[ 'password' ]) ? $this->config[ 'password' ] : '';
            $username = isset($this->config[ 'username' ]) ? $this->config[ 'username' ] : '';

            $clusterBuilder = Cassandra::cluster()
              ->withContactPoints($host)
              ->withPort($port);

            if (!empty($this->config[ 'ssl' ][ 'enabled' ])) {
                if (!empty($this->config[ 'ssl' ][ 'verify' ])) {
                    $sslBuilder = Cassandra::ssl()->withVerifyFlags(Cassandra::VERIFY_PEER_CERT);
                } else {
                    $sslBuilder = Cassandra::ssl()->withVerifyFlags(Cassandra::VERIFY_NONE);
                }

                $clusterBuilder->withSSL($sslBuilder->build());
            }

            $clusterBuilder->withConnectTimeout($timeout);

            if ($username) {
                $clusterBuilder->withCredentials($username, $password);
            }

            $this->instance = $clusterBuilder->build()->connect();

            /**
             * In case of emergency:
             * $this->instance->execute(
             *      new Cassandra\SimpleStatement(sprintf("DROP KEYSPACE %s;", self::CASSANDRA_KEY_SPACE))
             * );
             */

            $this->instance->execute(new Cassandra\SimpleStatement(sprintf(
              "CREATE KEYSPACE IF NOT EXISTS %s WITH REPLICATION = { 'class' : 'SimpleStrategy', 'replication_factor' : 1 };",
              self::CASSANDRA_KEY_SPACE
            )));
            $this->instance->execute(new Cassandra\SimpleStatement(sprintf('USE %s;', self::CASSANDRA_KEY_SPACE)));
            $this->instance->execute(new Cassandra\SimpleStatement(sprintf('
                CREATE TABLE IF NOT EXISTS %s (
                    cache_uuid uuid,
                    cache_id varchar,
                    cache_data text,
                    cache_creation_date timestamp,
                    cache_expiration_date timestamp,
                    cache_length int,
                    PRIMARY KEY (cache_id)
                );', self::CASSANDRA_TABLE
            )));
        }

        return true;
    }

    /********************
     *
     * PSR-6 Extended Methods
     *
     *******************/

    /**
     * @return string
     */
    public function getHelp()
    {
        return <<<HELP
<p>
To install the php Cassandra extension via Pecl:
<code>sudo pecl install cassandra</code>
More information on: https://github.com/datastax/php-driver
Please not that this repository only provide php stubs and C/C++ sources, it does NOT provide php client.
</p>
HELP;
    }

    /**
     * @return DriverStatistic
     * @throws \Cassandra\Exception
     */
    public function getStats()
    {
        $result = $this->instance->execute(new Cassandra\SimpleStatement(sprintf(
          'SELECT SUM(cache_length) as cache_size FROM %s.%s',
          self::CASSANDRA_KEY_SPACE,
          self::CASSANDRA_TABLE
        )));

        return (new DriverStatistic())
          ->setSize($result->first()[ 'cache_size' ])
          ->setRawData([])
          ->setData(implode(', ', array_keys($this->itemInstances)))
          ->setInfo('The cache size represents only the cache data itself without counting data structures associated to the cache entries.');
    }
}