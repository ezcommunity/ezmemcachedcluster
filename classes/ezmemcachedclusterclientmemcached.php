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
     * Constructor.
     * An exception will be thrown if Memcached PECL extension is not installed.
     *
     * @throws RuntimeException
     */
    public function __construct()
    {
        if ( !ezcBaseFeatures::hasExtensionSupport( 'memcached' ) )
            throw new RuntimeException( __CLASS__ . 'needs "memcached" extension to be installed. See http://php.net/memcached' );
    }

    /**
     * Initializes the client with $options
     *
     * @param eZMemcachedClusterOptions $options
     * @return void
     */
    public function initialize( eZMemcachedClusterOptions $options )
    {
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
            eZDebug::writeError( 'A problem occurred while adding Memcached servers to client', __METHOD__ );
        }
    }
}
