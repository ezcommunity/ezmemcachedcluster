<?php
/**
 * File containing the eZMemcachedClusterClientMemcache class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

/**
 * Memcached client using Memcache PECL extension
 *
 * @see http://php.net/memcache
 */
class eZMemcachedClusterClientMemcache implements eZMemcachedClusterClient
{
    /**
     * Lock timeout, in seconds.
     */
    const LOCK_TIMEOUT = 3;

    /**
     * Memcached gateway
     *
     * @var Memcache
     */
    protected $gateway;

    /**
     * Options for the gateway
     *
     * @var eZMemcachedClusterOptions
     */
    protected $options;

    /**
     * If useCompression property is set in {@link $options}, will equals to MEMCACHE_COMPRESSED constant
     *
     * @var int
     */
    private $compressionFlag;

    /**
     * Constructor.
     * An exception will be thrown if Memcache PECL extension is not installed.
     *
     * @throws RuntimeException
     */
    public function __construct()
    {
        if ( !extension_loaded( 'memcache' ) )
            throw new RuntimeException( __CLASS__ . 'needs "memcache" extension to be installed. See http://php.net/memcache' );
    }

    /**
     * Initializes the client with $options
     *
     * @param eZMemcachedClusterOptions $options
     * @return void
     * @throws eZMemcachedException
     */
    public function initialize( eZMemcachedClusterOptions $options )
    {
        $this->gateway = new Memcache;
        $this->options = $options;

        $servers = array();
        foreach ( $options->servers as $serverSpec )
        {
            // Default server weight arbitrarily set to 10
            $weight = 10;
            if ( strpos( $serverSpec, ';' ) !== false )
            {
                list( $serverSpec, $weight ) = explode( ';', $serverSpec );
                $weight = (int)$weight;
            }

            list( $host, $port ) = explode( ':', $serverSpec );
            if ( !$this->gateway->addserver( $host, (int)$port, $options->usePersistentConnection, $weight, $options->connectTimeout ) )
                throw new eZMemcachedException( "A problem occurred while adding Memcached server '$host:$port'" );
        }

        if ( $options->useCompression )
            $this->compressionFlag = MEMCACHE_COMPRESSED;
    }

    /**
     * Gets a cached item identified by $key.
     * Returned value depends on what has been stored previously.
     * False will be returned if $key has not been found in Memcached backend.
     *
     * @param string $key
     * @return mixed
     * @throws eZMemcachedException
     */
    public function get( $key )
    {
        return $this->gateway->get( $this->options->prefixKey . $key, $this->compressionFlag );
    }

    /**
     * Caches an item in Memcached backend.
     * As CAS (check and set) mechanism is not available in Memcache PECL extension,
     * a lock-based mechanism will be introduced in order to avoid keys overwrite issues.
     * Lock flags are stored in Memcached, with a timeout (3s).
     * Keys for these flags are "writeLock:<keyToSet>". Value equals to 1.
     *
     * @param string $key Label for the value to store
     * @param mixed $value The value you want to store. Can be anything but a resource
     * @param int $ttl Time to live for this key/value pair. Can be:
     *                 - A relative value (in seconds), but cannot exceed 30 days
     *                 - A UNIX timestamp
     *                 - If set to 0, the cached value will never expire
     *
     * @return bool True if key/value pair has been stored properly, false otherwise
     * @throws eZMemcachedException
     */
    public function set( $key, $value, $ttl = null )
    {
        $lockKey = "writeLock:$key";
        $notAlreadyLocked = $this->gateway->add( $this->options->prefixKey . $lockKey, 1, null, self::LOCK_TIMEOUT );
        if ( $notAlreadyLocked )
        {
            if ( $ttl == null )
                $ttl = $this->options->defaultCacheTTL;

            if ( !$this->gateway->set( $this->options->prefixKey . $key, $value, $this->compressionFlag, $ttl ) )
                throw new eZMemcachedException( "An error occurred while trying to set value '$value' to key '$key'" );

            // Remove lock
            $this->gateway->delete( $this->options->prefixKey . $lockKey );
            return true;
        }

        return false;
    }

    /**
     * Deletes a cached item identified by $key
     *
     * @param string $key
     * @return void
     * @throws eZMemcachedException
     */
    public function delete( $key )
    {
        $metadata = $this->get( $key );
        $this->gateway->delete( $this->options->prefixKey . $key );
        if ( is_array( $metadata ) && isset( $metadata['name_trunk'] ) )
        {
            $map = $this->get( $metadata['name_trunk'] );
            if ( is_array( $map ) )
            {
                unset( $map[$key] );
                $result = $this->set( $metadata['name_trunk'], $map, 0 );
                while ( $result != true )
                {
                    $map = $this->get( $metadata['name_trunk'] );
                    if ( !is_array( $map ) || !isset( $map[$key] ) )
                        break;
                    unset( $map[$key] );
                    $result = $this->set( $metadata['name_trunk'], $map, 0 );
                }
            }
        }
    }

    /**
     * Flushes all cache from Memcached backend
     *
     * @param int $delay Delay before flushing server, in seconds.
     * @return void
     * @throws eZMemcachedException
     */
    public function flush( $delay = 0 )
    {
        $this->gateway->flush();
    }

    /**
     * Adds $value to the map identified by $mapId
     *
     * Reading and deleting map items is still done using {@see delete()} and {@see get()}
     *
     * @param string $mapId
     * @param string $value
     */
    public function addToMap( $mapId, $value )
    {
        $map = $this->get( $mapId );

        if ( $map === false || !is_array( $map ) )
        {
            $map = array( $value => true );
        }
        else
        {
            if ( isset( $map[$value] ) )
                return;
            $map[$value] = true;
        }

        // $result will be false if there is a write lock on $mapId key in Memcached
        // If so, we'll try to get the map again and add $value to it
        $result = $this->set( $mapId, $map, 0 );
        while ( $result != true )
        {
            $map = $this->get( $mapId );
            if ( isset( $map[$value] ) )
                break;
            $map[$value] = true;
            $result = $this->set( $mapId, $map, 0 );
        }
    }
}
