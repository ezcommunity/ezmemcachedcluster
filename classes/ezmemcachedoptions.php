<?php
/**
 * File containing the eZMemcachedClusterOptions class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

/**
 * Struct to define options to pass to Memcache client
 */
class eZMemcachedClusterOptions
{
    /**
     * Indicates if we want to use a persistent connection to the Memcached server
     *
     * @var bool
     */
    public $usePersistentConnection = true;

    /**
     * Identifier to use in order to set a persistent connection
     *
     * @var string
     */
    public $connectionIdentifier = 'ezcluster';

    /**
     * Array of Memcached servers to connect to, in the form <host>:<port>.
     * e.g. localhost:11211
     *
     * @var array
     */
    public $servers = array();

    /**
     * Use compression or not while communicating with Memcached server
     *
     * @var bool
     */
    public $useCompression = true;

    /**
     * Can be used to create a "domain" for item keys
     *
     * @var string
     */
    public $prefixKey = 'ezcluster-';

    /**
     * Buffered I/O usage.
     * Enabling buffered I/O causes storage commands to "buffer" instead of being sent.
     *
     * @var bool
     */
    public $useBuffer = false;

    /**
     * Binary protocol usage
     *
     * @var bool
     */
    public $useBinaryProtocol = false;

    /**
     * Connection timeout, in milliseconds
     *
     * @var int
     */
    public $connectTimeout = 1000;

    /**
     * Default TTL for items put in Memcached, in seconds
     *
     * @var int
     */
    public $defaultCacheTTL = 3600;
}
