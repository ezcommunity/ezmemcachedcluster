<?php
/**
 * File containing the eZMemcachedClusterClient interface
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

/**
 * Interface for Memcached clients
 */
interface eZMemcachedClusterClient
{
    /**
     * Initializes the client with $options
     *
     * @param eZMemcachedClusterOptions $options
     * @return void
     */
    public function initialize( eZMemcachedClusterOptions $options );

    /**
     * Gets a cached item identified by $key.
     * Returned value depends on what has been stored previously.
     * False will be returned if $key has not been found in Memcached backend.
     *
     * @param string $key
     * @return mixed
     * @throws eZMemcachedException
     */
    public function get( $key );

    /**
     * Caches an item in Memcached backend
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
    public function set( $key, $value, $ttl );

    /**
     * Deletes a cached item identified by $key
     *
     * @param string $key
     * @return void
     * @throws eZMemcachedException
     */
    public function delete( $key );

    /**
     * Flushes all cache from Memcached backend
     *
     * @param int $delay Delay before flushing server, in seconds.
     * @return void
     * @throws eZMemcachedException
     */
    public function flush( $delay = 0 );
}
