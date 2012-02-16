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

    }
}
