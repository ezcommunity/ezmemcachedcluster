<?php
/**
 * File containing the eZMemcachedClusterClientMemcached class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

/**
 * Memcached client using Memcached PECL extension
 *
 * @see http://php.net/memcached
 */
class eZMemcachedClusterClientMemcached implements eZMemcachedClusterClient
{
    /**
     * Memcached gateway
     *
     * @var Memcached
     */
    protected $gateway;

    /**
     * Hash of CAS tokens.
     * Key is the cached item key, value is the token.
     *
     * @see http://fr.php.net/manual/fr/memcached.cas.php
     * @var float[]
     */
    protected $tokens = array();

    /**
     * Options for the gateway
     *
     * @var eZMemcachedClusterOptions
     */
    protected $options;

    /**
     * Constructor.
     * An exception will be thrown if Memcached PECL extension is not installed.
     *
     * @throws RuntimeException
     */
    public function __construct()
    {
        if ( !extension_loaded( 'memcached' ) )
            throw new RuntimeException( __CLASS__ . 'needs "memcached" extension to be installed. See http://php.net/memcached' );
    }

    /**
     * Initializes the client with $options
     *
     * @param eZMemcachedClusterOptions $options
     * @return void
     * @throws eZMemcachedException When Memcached gateway finds a problem
     */
    public function initialize( eZMemcachedClusterOptions $options )
    {
        $this->options = $options;

        if ( $options->usePersistentConnection && $options->connectionIdentifier != '' )
            $this->gateway = new Memcached( $options->connectionIdentifier );
        else
            $this->gateway = new Memcached;

        // Now set options
        $this->gateway->setOption( Memcached::OPT_COMPRESSION, $options->useCompression );
        $this->gateway->setOption( Memcached::OPT_BUFFER_WRITES, $options->useBuffer );
        $this->gateway->setOption( Memcached::OPT_BINARY_PROTOCOL, $options->useBinaryProtocol );
        if ( $options->prefixKey )
            $this->gateway->setOption( Memcached::OPT_PREFIX_KEY, $options->prefixKey );

        // Connect to Memcached backend
        if ( $options->connectTimeout )
            $this->gateway->setOption( Memcached::OPT_CONNECT_TIMEOUT, $options->connectTimeout );
        $servers = array();
        foreach ( $options->servers as $serverSpec )
        {
            $weight = null;
            if ( strpos( $serverSpec, ';' ) !== false )
                list( $serverSpec, $weight ) = explode( ';', $serverSpec );

            list( $host, $port ) = explode( ':', $serverSpec );
            $server = array( $host, (int)$port );
            if ( isset( $weight ) )
                $server[] = $weight;

            $servers[] = $server;
        }

        if ( !$this->gateway->addServers( $servers ) )
        {
            $errCode = $this->gateway->getResultCode();
            $errMsg = $this->gateway->getResultMessage();
            throw new eZMemcachedException( $errMsg, $errCode );
        }
    }

    /**
     * Gets a cached item identified by $key.
     * Returned value depends on what has been stored previously.
     * False will be returned if $key has not been found in Memcached backend.
     *
     * @param string $key
     * @return mixed
     * @throws eZMemcachedException When Memcached gateway finds a problem (anything but Memcached::RES_NOTFOUND)
     */
    public function get( $key )
    {
        $item = $this->gateway->get( $key, null, $casToken );
        if ( $item !== false )
        {
            $this->tokens[$key] = $casToken;
            return $item;
        }
        else if ( $errCode = $this->gateway->getResultCode() != Memcached::RES_NOTFOUND )
        {
            $errMsg = $this->gateway->getResultMessage();
            throw new eZMemcachedException( $errMsg, $errCode );
        }

        return false;
    }

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
     * @return bool True if everything went OK, False otherwise (e.g. key already modified)
     * @throws eZMemcachedException When Memcached gateway finds a problem (anything but Memcached::RES_DATA_EXISTS)
     */
    public function set( $key, $value, $ttl = null )
    {
        if ( $ttl == null )
            $ttl = $this->options->defaultCacheTTL;

        if ( !isset( $this->tokens[$key] ) )
        {
            $item = $this->get( $key );
            // If item doesn't exist yet, we need to add it
            // Using add() instead of set() ensures that the value won't be overwritten
            // if the item has been created in the meantime
            // Re-doing a get() allows to fetch and store the CAS token
            if ( $item === false )
            {
                $success = $this->gateway->add( $key, $value, $ttl );
                $item = $this->get( $key );
            }
        }
        else
        {
            $success = $this->gateway->cas( $this->tokens[$key], $key, $value, $ttl );
        }

        if ( !$success )
        {
            $errCode = $this->gateway->getResultCode();
            $errMsg = $this->gateway->getResultMessage();

            if ( $errCode != Memcached::RES_DATA_EXISTS )
                throw new eZMemcachedException( $errMsg, $errCode );
        }

        return $success;
    }

    /**
     * Deletes a cached item identified by $key
     *
     * @param string $key
     * @return void
     * @throws eZMemcachedException When Memcached gateway finds a problem
     */
    public function delete( $key )
    {
        if ( !$this->gateway->delete( $key ) )
        {
            $errCode = $this->gateway->getResultCode();
            $errMsg = $this->gateway->getResultMessage();
            if ( $errCode != Memcached::RES_NOTFOUND )
                throw new eZMemcachedException( $errMsg, $errCode );
        }

        if ( isset( $this->tokens[$key] ) )
            unset( $this->tokens[$key] );
    }

    /**
     * Flushes all cache from Memcached backend
     *
     * @param int $delay Delay before flushing server, in seconds.
     * @return void
     * @throws eZMemcachedException When Memcached gateway finds a problem
     */
    public function flush( $delay = 0 )
    {
        if ( !$this->gateway->flush( $delay ) )
        {
            $errCode = $this->gateway->getResultCode();
            $errMsg = $this->gateway->getResultMessage();
            throw new eZMemcachedException( $errMsg, $errCode );
        }

        $this->tokens = array();
    }

    /**
     * Adds $value to the map identified by $mapId
     *
     * Reading and deleting map items is still done using {@link delete()} and {@link get()}
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

        do
        {
            $result = $this->set( $mapId, $map, 0 );
        }
        while( $result != true );
    }
}
